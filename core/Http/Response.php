<?php

declare(strict_types=1);

namespace Core\Http;

class Response
{
    /** Work that waits until the visitor already has the answer. */
    private array $deferred = [];

    /** @param array<string, string> $headers */
    public function __construct(
        protected string $content = '',
        protected int $status = 200,
        protected array $headers = [],
    ) {
        if (!isset($this->headers['Content-Type'])) {
            $this->headers['Content-Type'] = 'text/html; charset=utf-8';
        }
    }

    public static function make(string $content = '', int $status = 200, array $headers = []): static
    {
        return new static($content, $status, $headers);
    }

    /** @param array<string|int, mixed> $data */
    public static function json(array $data, int $status = 200, array $headers = []): JsonResponse
    {
        return new JsonResponse($data, $status, $headers);
    }

    /** @param array<string, mixed> $data */
    public static function view(string $template, array $data = [], int $status = 200): Response
    {
        $view = app(\Core\View\View::class);

        return new static($view->render($template, $data), $status);
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setStatus(int $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function header(string $name, string $value): static
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /** @return array<string, string> */
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * Do this after the answer is out of the door.
     *
     * A visitor who saves a piece in his box should not wait for the mail the
     * shop sends itself — on a real host that is an SMTP handshake of a second or
     * more. `$response->defer(fn () => $mailer->saved(...))` hands him the answer
     * first and does the slow part while the browser is already painting.
     */
    public function defer(callable $work): static
    {
        $this->deferred[] = $work;

        return $this;
    }

    public function send(): void
    {
        /* whatever buffers whoever called us is using: the page's own output is
           flushed, theirs is left alone */
        $level = ob_get_level();

        if (!headers_sent()) {
            http_response_code($this->status);
            foreach ($this->headers as $name => $value) {
                header($name . ': ' . $value);
            }
        }

        echo $this->content;

        if ($this->deferred === []) {
            return;
        }

        $this->release($level);

        foreach ($this->deferred as $work) {
            try {
                $work();
            } catch (\Throwable $e) {
                /* the visitor already has his answer: nothing here may reach him */
                \Core\Logging\Log::channel('app')->warning('deferred work failed', [
                    'error' => $e->getMessage(),
                    'class' => $e::class,
                ]);
            }
        }

        $this->deferred = [];
    }

    /**
     * Hand the body to the browser before the deferred work runs.
     *
     * Under PHP-FPM `fastcgi_finish_request()` closes the request outright, which
     * is exactly what is wanted; everywhere else the length is announced and the
     * buffers are flushed, so the browser is free to start on the page while the
     * shop finishes its own business.
     */
    private function release(int $level): void
    {
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();

            return;
        }

        if (!headers_sent()) {
            header('Connection: close');
            header('Content-Length: ' . strlen($this->content));
        }

        while (ob_get_level() > $level) {
            @ob_end_flush();
        }

        flush();
    }
}
