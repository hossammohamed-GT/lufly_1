<?php

declare(strict_types=1);

namespace Core\Http;

class Response
{
    private array $deferred = [];

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

    public static function json(array $data, int $status = 200, array $headers = []): JsonResponse
    {
        return new JsonResponse($data, $status, $headers);
    }

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

    public function headers(): array
    {
        return $this->headers;
    }

    public function defer(callable $work): static
    {
        $this->deferred[] = $work;

        return $this;
    }

    public function send(): void
    {
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
                \Core\Logging\Log::channel('app')->warning('deferred work failed', [
                    'error' => $e->getMessage(),
                    'class' => $e::class,
                ]);
            }
        }

        $this->deferred = [];
    }

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
