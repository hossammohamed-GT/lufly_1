<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\Mail\SmtpClient;
use Core\Logging\Log;
use Throwable;

/**
 * Outgoing mail for the whole platform.
 *
 * Three transports (see config/mail.php):
 *   log  — nothing leaves the server; the full MIME message is written to
 *          storage/logs/mail.log so a mail flow can be verified without a mailbox
 *   mail — PHP mail(), i.e. the MTA of the hosting account (XAMPP, cPanel)
 *   smtp — authenticated SMTP with a real mailbox (info@lufly.tr on any host)
 *
 * Messages are always multipart/alternative: a plain-text part for clients that
 * do not render HTML plus the designed HTML part. Nothing here throws: a broken
 * mailbox must never break a page — the failure is logged and reported by
 * lastError().
 */
class MailService
{
    private ?string $lastError = null;

    public function lastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Send an HTML message (kept signature-compatible with the original API).
     *
     * @param array<string, string> $headers extra headers; "Reply-To" and "From" win over the config
     */
    public function send(string $to, string $subject, string $body, array $headers = []): bool
    {
        return $this->sendHtml($to, $subject, $body, '', $headers);
    }

    /**
     * @param array<string, string> $headers extra headers ("Reply-To", "From", "X-…")
     */
    public function sendHtml(string $to, string $subject, string $html, string $text = '', array $headers = []): bool
    {
        $this->lastError = null;

        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->lastError = 'Invalid recipient: ' . $to;
            Log::channel('mail')->warning('mail.invalid_recipient', ['to' => $to]);

            return false;
        }

        $transport = (string) config('mail.transport', 'log');
        $message = $this->message($to, $subject, $html, $text, $headers);
        $sent = false;

        try {
            $sent = match ($transport) {
                'smtp' => $this->smtpTransport($message),
                'mail', 'sendmail' => $this->mailTransport($message),
                default => $this->logTransport($message),
            };
        } catch (Throwable $e) {
            $this->lastError = $e->getMessage();
            Log::channel('mail')->error('mail.failed', [
                'transport' => $transport,
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage(),
            ]);
        }

        Log::channel('mail')->info('mail.dispatch', [
            'transport' => $transport,
            'to' => $to,
            'subject' => $subject,
            'bytes' => strlen($message['body']),
            'sent' => $sent,
        ]);

        return $sent;
    }

    /** @param string[] $recipients */
    public function sendToMany(array $recipients, string $subject, string $body): int
    {
        $count = 0;
        foreach ($recipients as $recipient) {
            if ($this->send($recipient, $subject, $body)) {
                $count++;
            }
        }

        return $count;
    }

    /* ------------------------------------------------------------ transports */

    /** @param array<string, mixed> $message */
    private function logTransport(array $message): bool
    {
        $file = storage_path('logs/mail.log');
        if (!is_dir(dirname($file))) {
            @mkdir(dirname($file), 0775, true);
        }

        /* The full header block goes into the log (From/To/Subject, Reply-To,
           Message-ID, extra X- headers) so the file holds a complete, readable
           MIME message that can be pasted into a mail client for review. */
        $headerLines = [];
        foreach ($message['headers'] as $name => $value) {
            $headerLines[] = $name . ': ' . $value;
        }

        $block = sprintf(
            "========== %s ==========\n%s\n\n%s\n\n",
            date('Y-m-d H:i:s'),
            implode("\n", $headerLines),
            rtrim((string) $message['body']),
        );

        @file_put_contents($file, $block, FILE_APPEND | LOCK_EX);

        return true;
    }

    /** @param array<string, mixed> $message */
    private function mailTransport(array $message): bool
    {
        /** @var array<string, string> $headers */
        $headers = $message['headers'];
        unset($headers['To'], $headers['Subject']);

        $headerLines = [];
        foreach ($headers as $name => $value) {
            $headerLines[] = $name . ': ' . $value;
        }

        $from = (string) config('mail.from_address', '');
        $parameters = filter_var($from, FILTER_VALIDATE_EMAIL) ? '-f' . $from : '';

        $sent = @mail(
            (string) $message['to'],
            (string) $message['subject'],
            (string) $message['body'],
            implode("\r\n", $headerLines),
            $parameters,
        );

        if ($sent === false) {
            throw new \RuntimeException('PHP mail() refused the message (check the local mail configuration).');
        }

        return true;
    }

    /** @param array<string, mixed> $message */
    private function smtpTransport(array $message): bool
    {
        $config = (array) config('mail.smtp', []);
        $config['from_address'] = (string) config('mail.from_address', '');

        $client = new SmtpClient($config);
        $client->send($message);

        return true;
    }

    /* ------------------------------------------------------------- message */

    /**
     * @param array<string, string> $headers
     * @return array{to: string, subject: string, body: string, headers: array<string, string>, raw_headers: array<string, string>, from: string, from_name: string}
     */
    private function message(string $to, string $subject, string $html, string $text, array $headers): array
    {
        $fromAddress = $this->headerValue($headers, 'From') ?: (string) config('mail.from_address', 'no-reply@localhost');
        $fromName = (string) config('mail.from_name', 'LUFLY');
        if (preg_match('/^(.*)<(.+)>$/u', $fromAddress, $m) === 1) {
            $fromName = trim($m[1]) !== '' ? trim($m[1], " \t\"'") : $fromName;
            $fromAddress = trim($m[2]);
        }

        $replyTo = $this->headerValue($headers, 'Reply-To')
            ?: trim((string) config('mail.reply_to_address', ''));

        $boundary = 'lufly-' . bin2hex(random_bytes(12));

        $plain = $text !== '' ? $text : $this->plainFromHtml($html);

        $body = "This is a multi-part message in MIME format.\r\n\r\n"
            . '--' . $boundary . "\r\n"
            . "Content-Type: text/plain; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: base64\r\n\r\n"
            . chunk_split(base64_encode($plain), 76, "\r\n") . "\r\n"
            . '--' . $boundary . "\r\n"
            . "Content-Type: text/html; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: base64\r\n\r\n"
            . chunk_split(base64_encode($html), 76, "\r\n") . "\r\n"
            . '--' . $boundary . "--\r\n\r\n";

        $raw = [
            'Date' => date('r'),
            'From' => $this->address($fromName, $fromAddress),
            'To' => $to,
            'Subject' => $this->encodeHeader($subject),
            'Message-ID' => '<' . bin2hex(random_bytes(10)) . '@' . $this->hostname() . '>',
            'MIME-Version' => '1.0',
            'Content-Type' => 'multipart/alternative; boundary="' . $boundary . '"',
            'X-Mailer' => 'LUFLY Platform',
        ];

        if ($replyTo !== '' && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
            $raw['Reply-To'] = $this->address((string) config('mail.reply_to_name', '') ?: $fromName, $replyTo);
        }

        foreach ($headers as $name => $value) {
            if (in_array(strtolower($name), ['from', 'to', 'subject', 'reply-to'], true)) {
                continue;
            }
            $raw[$name] = $value;
        }

        return [
            'to' => $to,
            'from' => $fromAddress,
            'from_name' => $fromName,
            'subject' => $subject,
            'body' => $body,
            'headers' => $raw,
            'raw_headers' => $raw,
        ];
    }

    /** @param array<string, string> $headers */
    private function headerValue(array $headers, string $name): string
    {
        foreach ($headers as $key => $value) {
            if (strcasecmp($key, $name) === 0) {
                return trim($value);
            }
        }

        return '';
    }

    private function address(string $name, string $address): string
    {
        if ($name === '') {
            return $address;
        }

        return $this->encodeHeader($name) . ' <' . $address . '>';
    }

    private function encodeHeader(string $value): string
    {
        if (preg_match('/[\x80-\xFF]/', $value) === 1) {
            return '=?UTF-8?B?' . base64_encode($value) . '?=';
        }

        return $value;
    }

    private function hostname(): string
    {
        $host = parse_url((string) config('app.url', ''), PHP_URL_HOST);

        return is_string($host) && $host !== '' ? $host : (gethostname() ?: 'lufly.local');
    }

    /** Crude but readable text fallback for messages that only carry HTML. */
    private function plainFromHtml(string $html): string
    {
        $text = preg_replace('#<(script|style)[^>]*>.*?</\1>#is', '', $html) ?? $html;
        $text = preg_replace('#<br\s*/?>#i', "\n", $text) ?? $text;
        $text = preg_replace('#</(p|div|h1|h2|h3|li|tr)>#i', "\n", $text) ?? $text;
        $text = preg_replace('#<li[^>]*>#i', ' - ', $text) ?? $text;
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/[ \t]+/", ' ', $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }
}
