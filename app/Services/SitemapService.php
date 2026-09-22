<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\Seo\UrlLocalizer;

/**
 * Dynamic XML sitemap generation. Every public URL is derived from the
 * routing + translation layer, so the sitemap can never advertise a URL
 * that 404s, and hreflang pairs always point at real translated paths.
 */
class SitemapService
{
    public function __construct(private readonly UrlLocalizer $urls)
    {
    }

    /** Static storefront pages in crawl-priority order. */
    private const STATIC_PAGES = [
        ['key' => 'home',            'priority' => '1.0', 'changefreq' => 'daily'],
        ['key' => 'products.index',  'priority' => '0.9', 'changefreq' => 'daily'],
        ['key' => 'contact',         'priority' => '0.7', 'changefreq' => 'monthly'],
    ];

    /* ------------------------------------------------ urls ---------- */

    /** @return list<array<string, mixed>> */
    public function pageEntries(): array
    {
        $lastmod = (string) config('seo.sitemap_static_lastmod', date('Y-m-d'));
        $entries = [];

        foreach (self::STATIC_PAGES as $page) {
            foreach ($this->urls->locales() as $locale) {
                $entries[] = [
                    'loc' => $this->urls->localizedUrl($page['key'], $locale),
                    'alternates' => $this->urls->alternates($page['key']),
                    'images' => [],
                    'lastmod' => $lastmod,
                    'changefreq' => $page['changefreq'],
                    'priority' => $page['priority'],
                ];
            }
        }

        return $entries;
    }

    /**
     * Streams product entries in bounded chunks to avoid loading large catalogs and all media into memory at once.
     *
     * @param int $chunkSize
     * @return \Generator<int, array<string, mixed>>
     */
    public function productEntriesGenerator(int $chunkSize = 250): \Generator
    {
        $connection = \Modules\Products\Models\Product::query()->connection();
        $lastId = 0;

        while (true) {
            $products = (array) $connection->select(
                "SELECT id, slug, updated_at
                   FROM products
                  WHERE status = 'active' AND deleted_at IS NULL AND id > ?
                  ORDER BY id ASC
                  LIMIT ?",
                [$lastId, $chunkSize]
            );

            if (empty($products)) {
                break;
            }

            $productIds = array_map(static fn (array $p): int => (int) $p['id'], $products);
            $lastId = (int) end($productIds);

            $placeholders = implode(',', array_fill(0, count($productIds), '?'));
            $imageRows = (array) $connection->select(
                "SELECT pm.product_id, m.path
                   FROM product_media pm
                   INNER JOIN media m ON m.id = pm.media_id
                  WHERE pm.product_id IN ({$placeholders})
                    AND pm.variant_id IS NULL
                    AND m.status <> 'missing'
                  ORDER BY pm.is_primary DESC, pm.sort_order ASC",
                $productIds
            );

            $imagesByProduct = [];
            foreach ($imageRows as $row) {
                $imagesByProduct[(int) $row['product_id']][] = (string) $row['path'];
            }
            unset($imageRows);

            foreach ($products as $product) {
                $slug = (string) $product['slug'];
                if ($slug === '') {
                    continue;
                }
                yield [
                    'loc' => $this->urls->localizedUrl('products.show', 'en', ['slug' => $slug]),
                    'locales' => $this->buildProductLocs($slug),
                    'alternates' => $this->urls->alternates('products.show', ['slug' => $slug]),
                    'images' => $imagesByProduct[(int) $product['id']] ?? [],
                    'lastmod' => substr((string) ($product['updated_at'] ?? ''), 0, 10) ?: date('Y-m-d'),
                    'changefreq' => 'weekly',
                    'priority' => '0.8',
                ];
            }
            unset($products, $imagesByProduct);
        }
    }

    /** @return list<array<string, mixed>> */
    public function productEntries(): array
    {
        return iterator_to_array($this->productEntriesGenerator());
    }

    /** Every locale renders one <url> block; carried separately so images can repeat per URL without re-querying. */
    /** @return array<string, string> */
    private function buildProductLocs(string $slug): array
    {
        $locs = [];
        foreach ($this->urls->locales() as $locale) {
            $locs[$locale] = $this->urls->localizedUrl('products.show', $locale, ['slug' => $slug]);
        }
        return $locs;
    }

    /* ------------------------------------------------ xml ----------- */

    /** Sitemap index referencing the sub-sitemaps. */
    public function renderIndex(): string
    {
        $lastmod = date('Y-m-d');
        $items = [
            url('/sitemaps/pages.xml'),
            url('/sitemaps/products.xml'),
            url('/sitemaps/images.xml'),
        ];

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($items as $loc) {
            $xml .= '  <sitemap>'
                . '<loc>' . $this->esc($loc) . '</loc>'
                . '<lastmod>' . $this->esc($lastmod) . '</lastmod>'
                . '</sitemap>' . "\n";
        }
        return $xml . '</sitemapindex>';
    }

    /** urlset from entries (see pageEntries/productEntries). */
    /** @param iterable<array<string, mixed>> $entries */
    public function renderUrlset(iterable $entries, bool $withImages = true): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"'
            . ' xmlns:xhtml="http://www.w3.org/1999/xhtml"'
            . ($withImages ? ' xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"' : '')
            . '>' . "\n";

        foreach ($entries as $entry) {
            $xml .= "  <url>\n";
            $xml .= '    <loc>' . $this->esc((string) $entry['loc']) . "</loc>\n";

            foreach ((array) ($entry['alternates'] ?? []) as $lang => $href) {
                $xml .= '    <xhtml:link rel="alternate" hreflang="' . $this->esc((string) $lang) . '" href="' . $this->esc((string) $href) . '"/>' . "\n";
            }

            if (!empty($entry['alternates'])) {
                $default = (string) config('localization.default', 'en');
                $xDefault = $entry['alternates'][$default] ?? reset($entry['alternates']);
                $xml .= '    <xhtml:link rel="alternate" hreflang="x-default" href="' . $this->esc((string) $xDefault) . '"/>' . "\n";
            }

            if (!empty($entry['lastmod'])) {
                $xml .= '    <lastmod>' . $this->esc((string) $entry['lastmod']) . "</lastmod>\n";
            }
            if (!empty($entry['changefreq'])) {
                $xml .= '    <changefreq>' . $this->esc((string) $entry['changefreq']) . "</changefreq>\n";
            }
            if (!empty($entry['priority'])) {
                $xml .= '    <priority>' . $this->esc((string) $entry['priority']) . "</priority>\n";
            }

            if ($withImages) {
                foreach (array_slice((array) ($entry['images'] ?? []), 0, 10) as $path) {
                    $xml .= "    <image:image>\n"
                        . '      <image:loc>' . $this->esc(asset((string) $path)) . "</image:loc>\n"
                        . "    </image:image>\n";
                }
            }

            $xml .= "  </url>\n";
        }

        return $xml . '</urlset>';
    }

    /** Dedicated image sitemap: one <url> per product carrying all its media. */
    /** @param iterable<array<string, mixed>> $entries */
    public function renderImageSitemap(iterable $entries): string
    {
        return $this->renderUrlset($entries, true);
    }

    private function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
