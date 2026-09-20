<?php

declare(strict_types=1);

namespace App\Services\Seo;

use Core\Localization\Translator;

/**
 * Builds absolute URLs for any locale, honouring the translated route
 * slugs (resources/lang/{locale}/routes.php). This is the single source
 * of truth for hreflang alternates and sitemap locations, so a slug can
 * never drift between the three places that emit it.
 */
class UrlLocalizer
{
    public function __construct(private readonly Translator $translator)
    {
    }

    /**
     * Absolute URL of $routeKey in $locale, e.g.
     * localizedUrl('products.show', 'tr', ['slug' => 'x']) -> https://host/tr/urunler/x
     *
     * @param array<string, string> $params placeholder substitutions ({slug})
     */
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

    /**
     * hreflang pair set for a route: one absolute URL per supported locale.
     *
     * @param array<string, array<string, string>> $paramsPerLocale optional
     *        per-locale placeholder values (same key for all locales today)
     * @return array<string, string> e.g. ['en' => '.../en/products', ...]
     */
    public function alternates(string $routeKey, array $params = []): array
    {
        $out = [];
        foreach (array_keys($this->translator->supported()) as $locale) {
            $out[(string) $locale] = $this->localizedUrl($routeKey, (string) $locale, $params);
        }
        return $out;
    }

    /** @return list<string> */
    public function locales(): array
    {
        return array_map('strval', array_keys($this->translator->supported()));
    }
}
