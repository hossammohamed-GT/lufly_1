<?php

declare(strict_types=1);

namespace App\Services;

use Core\Foundation\Application;

class CacheService
{
    public function __construct(private readonly Application $app)
    {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $payload = $this->read($key);
        if ($payload === null) {
            return $default;
        }
        if (($payload['expires'] ?? 0) < time()) {
            $this->forget($key);
            return $default;
        }

        return $payload['value'] ?? $default;
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $ttl ??= (int) config('cache.default_ttl', 3600);

        try {
            $payload = json_encode(['expires' => time() + $ttl, 'value' => $value], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } catch (\JsonException) {
            $payload = serialize(['expires' => time() + $ttl, 'value' => $value]);
        }

        $path = $this->path($key);
        @mkdir(dirname($path), 0775, true);

        return file_put_contents($path, $payload, LOCK_EX) !== false;
    }

    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        if ($this->has($key)) {
            return $this->get($key);
        }

        $value = $callback();
        $this->set($key, $value, $ttl);

        return $value;
    }

    public function has(string $key): bool
    {
        $payload = $this->read($key);

        return $payload !== null && ($payload['expires'] ?? 0) >= time();
    }

    public function forget(string $key): bool
    {
        $path = $this->path($key);

        return is_file($path) ? unlink($path) : true;
    }

    public function flush(): void
    {
        foreach ((array) glob($this->app->storagePath('cacheprivate function read(string $key): ?array
    {
        $path = $this->path($key);
        if (!is_file($path)) {
            return null;
        }

        $raw = (string) file_get_contents($path);
        if (trim($raw) === '') {
            return null;
        }

        try {
            $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            if (is_array($data) && array_key_exists('expires', $data)) {
                return $data;
            }
        } catch (\JsonException) {

        }

        $payload = @unserialize($raw, ['allowed_classes' => false]);

        if (is_array($payload) && array_key_exists('value', $payload) && !is_object($payload['value'])) {
            return $payload;
        }

        return null;
    }

    private function path(string $key): string
    {
        $prefix = (string) config('cache.prefix', 'lufly_');

        return $this->app->storagePath('cache/' . $prefix . sha1($key) . '.cache');
    }
}
