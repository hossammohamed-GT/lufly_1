<?php

declare(strict_types=1);

namespace Core\Http;

use Core\Exceptions\ValidationException;
use Core\Validation\Validator;

class Request
{
    /** @var array<string, string> */
    private array $routeParams = [];

    private string $locale = '';

    private ?Route $route = null;

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     * @param array<string, mixed> $cookies
     * @param array<string, mixed> $files
     * @param array<string, mixed> $server
     */
    public function __construct(
        private readonly array $query = [],
        private readonly array $post = [],
        private readonly array $cookies = [],
        private readonly array $files = [],
        private readonly array $server = [],
        private readonly string $rawBody = '',
    ) {
    }

    public static function capture(): self
    {
        return new self($_GET ?? [], $_POST ?? [], $_COOKIE ?? [], $_FILES ?? [], $_SERVER ?? [], file_get_contents('php://input') ?: '');
    }

    public function method(): string
    {
        $method = strtoupper((string) ($this->server('REQUEST_METHOD') ?? 'GET'));
        if ($method === 'POST') {
            $override = $this->post['_method'] ?? $this->header('HTTP_X_HTTP_METHOD_OVERRIDE');
            if (is_string($override) && in_array(strtoupper($override), ['PUT', 'PATCH', 'DELETE'], true)) {
                return strtoupper($override);
            }
        }

        return $method;
    }

    public function isMethod(string $method): bool
    {
        return $this->method() === strtoupper($method);
    }

    public function path(): string
    {
        $uri = (string) ($this->server('REQUEST_URI') ?? '/');
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        $base = (string) parse_url((string) config('app.url', ''), PHP_URL_PATH);
        $base = rtrim($base, '/');
        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base)) ?: '/';
        }

        return '/' . ltrim($path, '/');
    }

    /** @return string[] */
    public function segments(): array
    {
        return array_values(array_filter(explode('/', trim($this->path(), '/'))));
    }

    public function server(string $key, mixed $default = null): mixed
    {
        return $this->server[$key] ?? $default;
    }

    public function header(string $key, mixed $default = null): mixed
    {
        $normalized = 'HTTP_' . strtoupper(str_replace('-', '_', $key));

        return $this->server[$key] ?? $this->server[$normalized] ?? $default;
    }

    public function bearerToken(): ?string
    {
        $header = (string) $this->header('HTTP_AUTHORIZATION', '');
        if (str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }

        return null;
    }

    public function ip(): string
    {
        return (string) ($this->server('REMOTE_ADDR') ?? '0.0.0.0');
    }

    public function userAgent(): string
    {
        return (string) $this->server('HTTP_USER_AGENT', '');
    }

    public function wantsJson(): bool
    {
        $accept = (string) $this->header('HTTP_ACCEPT', '');
        return str_contains($accept, 'application/json')
            || str_starts_with($this->path(), '/api')
            || $this->header('HTTP_X_REQUESTED_WITH') === 'XMLHttpRequest';
    }

    public function expectsJson(): bool
    {
        return $this->wantsJson();
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        $input = array_merge($this->query, $this->post);
        $json = $this->jsonPayload();

        return is_array($json) ? array_merge($input, $json) : $input;
    }

    /** @return array<string, mixed>|null */
    public function jsonPayload(): ?array
    {
        $contentType = (string) ($this->server('HTTP_CONTENT_TYPE') ?? $this->server('CONTENT_TYPE') ?? '');
        if ($this->rawBody !== '' && str_contains($contentType, 'application/json')) {
            $decoded = json_decode($this->rawBody, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        $all = $this->all();

        return array_key_exists($key, $all) ? $all[$key] : $default;
    }

    public function query(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->query;
        }

        return $this->query[$key] ?? $default;
    }

    public function post(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->post;
        }

        return $this->post[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->all());
    }

    public function filled(string $key): bool
    {
        $value = $this->input($key);

        return $value !== null && $value !== '';
    }

    /** @param string[] $keys */
    public function only(array $keys): array
    {
        return array_intersect_key($this->all(), array_flip($keys));
    }

    /** @return array<string, mixed>|null */
    public function file(string $key): ?array
    {
        $file = $this->files[$key] ?? null;

        return is_array($file) && isset($file['error']) ? $file : null;
    }

    public function hasFile(string $key): bool
    {
        $file = $this->file($key);

        return $file !== null && $file['error'] !== UPLOAD_ERR_NO_FILE;
    }

    /** @return array<string, mixed> */
    public function allFiles(): array
    {
        return $this->files;
    }

    public function cookie(string $key, mixed $default = null): mixed
    {
        return $this->cookies[$key] ?? $default;
    }

    public function url(): string
    {
        $scheme = (!empty($this->server['HTTPS']) && $this->server['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = (string) $this->server('HTTP_HOST', 'localhost');

        return $scheme . '://' . $host . $this->path();
    }

    /** @param array<string, string> $params */
    public function setRouteParams(array $params): void
    {
        $this->routeParams = $params;
    }

    public function param(string $key, mixed $default = null): mixed
    {
        return $this->routeParams[$key] ?? $default;
    }

    /** @return array<string, string> */
    public function params(): array
    {
        return $this->routeParams;
    }

    public function setRoute(Route $route): void
    {
        $this->route = $route;
    }

    public function route(): ?Route
    {
        return $this->route;
    }

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    public function locale(): string
    {
        return $this->locale !== '' ? $this->locale : (string) config('localization.default', 'en');
    }

    /**
     * Validate request input; throws ValidationException on failure.
     *
     * @param array<string, string> $rules
     * @return array<string, mixed> validated input
     */
    public function validate(array $rules): array
    {
        $validator = new Validator($this->all(), $rules, $this->allFiles());

        if ($validator->fails()) {
            $errors = $validator->errors();

            if (!$this->expectsJson()) {
                $session = app(Session::class);
                $session->flash('_errors', $errors);
                $session->flash('_old_input', $this->all());
            }

            throw new ValidationException($errors);
        }

        return $validator->validated();
    }
}
