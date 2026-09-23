<?php

declare(strict_types=1);

namespace App\Services\Mail;

use RuntimeException;

/**
 * Minimal SMTP client — no Composer, no external library.
 *
 * Supports the three shapes real mailboxes use:
 *   - implicit TLS      (MAIL_ENCRYPTION=ssl,  port 465)
 *   - STARTTLS          (MAIL_ENCRYPTION=tls,  port 587)
 *   - plain, no auth    (MAIL_ENCRYPTION=none, localhost / intranet relay)
 *
 * It speaks the smallest useful subset: EHLO, STARTTLS, AUTH LOGIN (with an
 * AUTH PLAIN fallback), MAIL FROM, RCPT TO, DATA with dot-stuffing and QUIT.
 * Every server reply is checked; failures are thrown as RuntimeException and
 * logged by MailService so a broken mailbox never breaks a page.
 *
 * The stream is opened through openStream() so the dialogue can be tested
 * without a network (see the favourites test harness).
 */
class SmtpClient
{
    /** @var resource|null */
    protected $stream;

    /** Transcript of the conversation (used by the mail log). */
    private string $transcript = '';

    /** @param array<string, mixed> $config */
    public function __construct(private readonly array $config = [])
    {
    }

    public function transcript(): string
    {
        return $this->transcript;
    }

    /**
     * @param array{
     *     from: string,
     *     from_name: string,
     *     to: string,
     *     subject: string,
     *     body: string,
     *     headers?: array<string, string>,
     * } $message
     */
    public function send(array $message): void
    {
        $host = (string) ($this->config['host'] ?? '127.0.0.1');
        $port = (int) ($this->config['port'] ?? 587);
        $encryption = strtolower(trim((string) ($this->config['encryption'] ?? '')));
        $timeout = max(3, (int) ($this->config['timeout'] ?? 15));
        $username = (string) ($this->config['username'] ?? '');
        $password = (string) ($this->config['password'] ?? '');
        $useAuth = (bool) ($this->config['auth'] ?? true) && $username !== '';
        $helo = trim((string) ($this->config['helo'] ?? '')) ?: $this->defaultHelo();

        $implicitTls = in_array($encryption, ['ssl', 'smtps', 'tls-implicit'], true);
        $startTls = in_array($encryption, ['tls', 'starttls', 'starttls-required'], true);

        if (($implicitTls || $startTls) && !extension_loaded('openssl')) {
            throw new RuntimeException('SMTP needs the openssl extension for encrypted connections.');
        }

        $target = ($implicitTls ? 'ssl://' : 'tcp://') . $host . ':' . $port;

        $this->transcript = '';
        $this->stream = $this->openStream($target, $timeout);

        try {
            $this->expect([220]);                                   // greeting
            $this->command('EHLO ' . $helo, [250]);

            if ($startTls) {
                $this->command('STARTTLS', [220]);
                $negotiated = @stream_socket_enable_crypto(
                    $this->stream,
                    true,
                    STREAM_CRYPTO_METHOD_TLS_CLIENT,
                );
                if ($negotiated !== true) {
                    throw new RuntimeException('SMTP STARTTLS negotiation failed.');
                }
                $this->command('EHLO ' . $helo, [250]);
            }

            if ($useAuth) {
                $this->authenticate($username, $password);
            }

            $this->command('MAIL FROM:<' . $message['from'] . '>', [250]);
            $this->command('RCPT TO:<' . $message['to'] . '>', [250, 251]);
            $this->command('DATA', [354]);

            $this->write($this->data($message));
            $this->expect([250]);                                   // message accepted

            $this->command('QUIT', [221], true);                     // best effort
        } finally {
            if (is_resource($this->stream)) {
                @fclose($this->stream);
            }
            $this->stream = null;
        }
    }

    /**
     * Open the socket. Overridable so the SMTP dialogue can be exercised in a
     * test without touching the network.
     *
     * @return resource
     */
    protected function openStream(string $target, int $timeout)
    {
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
                'allow_self_signed' => false,
                'SNI_enabled' => true,
            ],
        ]);

        $errno = 0;
        $errstr = '';
        $stream = @stream_socket_client(
            $target,
            $errno,
            $errstr,
            $timeout,
            STREAM_CLIENT_CONNECT,
            $context,
        );

        if ($stream === false) {
            throw new RuntimeException(sprintf(
                'Could not connect to SMTP server %s (%s%s).',
                $target,
                $errstr !== '' ? $errstr : 'unknown error',
                $errno !== 0 ? ' #' . $errno : '',
            ));
        }

        stream_set_timeout($stream, $timeout);

        return $stream;
    }

    private function defaultHelo(): string
    {
        $from = (string) ($this->config['from_address'] ?? '');
        $domain = str_contains($from, '@') ? substr($from, strpos($from, '@') + 1) : '';

        return $domain !== '' ? $domain : (gethostname() ?: 'localhost');
    }

    private function authenticate(string $username, string $password): void
    {
        $this->command('AUTH LOGIN', [334]);

        try {
            $this->command(base64_encode($username), [334]);
            $this->command(base64_encode($password), [235]);
            return;
        } catch (RuntimeException $e) {
            /* Server refused AUTH LOGIN (some do) — fall back to AUTH PLAIN. */
            if (!str_contains($e->getMessage(), '504') && !str_contains($e->getMessage(), '500')) {
                throw $e;
            }
        }

        $plain = base64_encode("\0" . $username . "\0" . $password);
        $this->command('AUTH PLAIN ' . $plain, [235]);
    }

    /** @param array<string, mixed> $message */
    private function data(array $message): string
    {
        $headers = $message['headers'] ?? [];
        $lines = [];
        foreach ($headers as $name => $value) {
            $lines[] = $name . ': ' . $value;
        }
        $lines[] = '';
        $lines[] = (string) $message['body'];

        $body = implode("\r\n", $lines);

        /* Normalise line endings, then dot-stuff (RFC 5321 4.5.2). */
        $body = str_replace(["\r\n", "\r"], "\n", $body);
        $body = implode("\r\n", array_map(
            static fn (string $line): string => str_starts_with($line, '.') ? '.' . $line : $line,
            explode("\n", $body),
        ));

        return $body . "\r\n.\r\n";
    }

    /** @param int[] $expected */
    private function command(string $line, array $expected, bool $tolerateFailure = false): void
    {
        $this->write($line . "\r\n");

        try {
            $this->expect($expected);
        } catch (RuntimeException $e) {
            if (!$tolerateFailure) {
                throw $e;
            }
        }
    }

    private function write(string $data): void
    {
        if (!is_resource($this->stream)) {
            throw new RuntimeException('SMTP connection is not open.');
        }

        @fwrite($this->stream, $data);
        $this->transcript .= '> ' . trim($data) . "\n";
    }

    /** @param int[] $expected */
    private function expect(array $expected): string
    {
        $response = '';
        $code = 0;

        for ($i = 0; $i < 50; $i++) {
            $line = $this->readLine();
            if ($line === null) {
                throw new RuntimeException('SMTP server closed the connection unexpectedly.');
            }

            $response .= $line;
            $code = (int) substr(trim($line), 0, 3);

            /* multi-line replies continue while the 4th character is "-" */
            if (strlen(trim($line)) > 3 && substr(trim($line), 3, 1) === '-') {
                $response .= "\n";
                continue;
            }

            break;
        }

        if (!in_array($code, $expected, true)) {
            throw new RuntimeException(sprintf(
                'SMTP server answered %d (%s), expected %s.',
                $code,
                trim(str_replace("\n", ' | ', $response)),
                implode('/', $expected),
            ));
        }

        return $response;
    }

    private function readLine(): ?string
    {
        if (!is_resource($this->stream)) {
            return null;
        }

        $line = @fgets($this->stream, 4096);
        if ($line === false) {
            return null;
        }

        $this->transcript .= '< ' . rtrim($line) . "\n";

        return $line;
    }
}
