<?php

declare(strict_types=1);

namespace Core\Localization;

use Core\Foundation\Application;

class Translator
{
    private string $locale;

    private string $fallback;

    /** @var array<string, array<string, mixed>> "locale:file" => entries */
    private array $loaded = [];

    /** @var array<string, string> locale => native name */
    private array $supported;

    public function __construct(private readonly Application $app)
    {
        $this->supported = (array) config('localization.supported', ['en' => 'English']);
        $this->locale = (string) config('localization.default', 'en');
        $this->fallback = (string) config('localization.fallback', 'en');
    }

    /** @param array<string, mixed> $params */
    public function trans(string $key, array $params = [], ?string $locale = null): string
    {
        $locale ??= $this->locale;

        $value = $this->resolve($key, $locale)
            ?? $this->resolve($key, $this->fallback)
            ?? $key;

        foreach ($params as $name => $param) {
            $value = str_replace(':' . $name, (string) $param, $value);
            $value = str_replace('{' . $name . '}', (string) $param, $value);
        }

        return $value;
    }

    private function resolve(string $key, string $locale): ?string
    {
        [$file, $path] = $this->splitKey($key);
        if ($path === null) {
            return null;
        }

        $entries = $this->loadFile($locale, $file);
        if (isset($entries[$path]) && is_string($entries[$path])) {
            return $entries[$path];
        }

        $value = $entries;
        foreach (explode('.', $path) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return null;
            }
            $value = $value[$segment];
        }

        return is_string($value) ? $value : null;
    }

    /** @return array{0: string, 1: string|null} file, remaining key path */
    private function splitKey(string $key): array
    {
        if (!str_contains($key, '.')) {
            return [$key, null];
        }

        [$file, $path] = explode('.', $key, 2);

        return [$file, $path];
    }

    /** @return array<string, mixed> */
    private function loadFile(string $locale, string $file): array
    {
        $cacheKey = $locale . ':' . $file;
        if (isset($this->loaded[$cacheKey])) {
            return $this->loaded[$cacheKey];
        }

        $path = $this->app->resourcePath("lang/{$locale}/{$file}.php");
        $entries = is_file($path) ? (array) require $path : [];

        return $this->loaded[$cacheKey] = $entries;
    }

    public function setLocale(string $locale): void
    {
        if ($this->isSupported($locale)) {
            $this->locale = $locale;
        }
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function getFallback(): string
    {
        return $this->fallback;
    }

    public function isSupported(string $locale): bool
    {
        return isset($this->supported[$locale]);
    }

    /** @return array<string, string> */
    public function supported(): array
    {
        return $this->supported;
    }

    /** @return string[] */
    public function locales(): array
    {
        return array_keys($this->supported);
    }

    public function nativeName(string $locale): string
    {
        return $this->supported[$locale] ?? $locale;
    }
}
