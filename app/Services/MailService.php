<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\Mail\SmtpClient;
use Core\Logging\Log;
use Throwable;

class MailService
{
    private ?string $lastError = null;

    public function lastError(): ?string
    {
        return $this->lastError;
    }

    public function send(string $to, string $subject, string $body, array $headers = []): bool
    {
        return $this->sendHtml($to, $subject, $body, '', $headers);
    }

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

    private function logTransport(array $message): bool
    {
        $file = storage_path('logs/mail.log');
        if (!is_dir(dirname($file))) {
            @mkdir(dirname($file), 0775, true);
        }

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

    private function mailTransport(array $message): bool
    {
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

    private function smtpTransport(array $message): bool
    {
        $config = (array) config('mail.smtp', []);
        $config['from_address'] = (string) config('mail.from_address', '');

        $client = new SmtpClient($config);
        $client->send($message);

        return true;
    }

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
