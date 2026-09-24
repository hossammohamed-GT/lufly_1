<?php

declare(strict_types=1);

namespace App\Services\Seo;

use Core\Localization\Translator;

class UrlLocalizer
{
    public function __construct(private readonly Translator $translator)
    {
    }

    public function localizedUrl(string $routeKey, string $locale, array $params = []): string
    {
        $translated = $this->translator->trans('routes.' . $routeKey, [], $locale);
        if ($translated === 'routes.' . $routeKey) {
            $translated = $routeKey;
        }

        $path = '/' . $locale . '/' . ltrim($translated, '/');

        if ($params !== []) {
            $path = (string) preg_replace_callback(
                '/\{(\w+)\}/',
                static function (array $m) use (&$params): string {
                    $key = $m[1];
                    $value = (string) ($params[$key] ?? '');
                    unset($params[$key]);
                    return rawurlencode($value);
                },
                $path
            );
        }

        return url(rtrim($path, '/') ?: '/');
    }

    public function alternates(string $routeKey, array $params = []): array
    {
        $out = [];
        foreach (array_keys($this->translator->supported()) as $locale) {
            $out[(string) $locale] = $this->localizedUrl($routeKey, (string) $locale, $params);
        }
        return $out;
    }

    public function locales(): array
    {
        return array_map('strval', array_keys($this->translator->supported()));
    }
}
