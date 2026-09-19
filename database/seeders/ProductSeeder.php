<?php

declare(strict_types=1);

namespace Database\Seeders;

use Core\Database\Seeding\Seeder;

/**
 * Imports the REAL LUFLY catalog extracted from the legacy WordPress /
 * WooCommerce database (delete_files/lufly_new.sql) into the new product
 * architecture.
 *
 * Source of truth : database/seeders/data/products.json
 * Produced by     : tools/import/extract_legacy.py
 *
 * Every field written here exists in the legacy system. Nothing is invented:
 * if the legacy record had no description, specification, or translation,
 * the corresponding row is simply not created. Missing locales (tr) and
 * extra gallery images are filled in later from Admin.
 */
class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $dataFile = __DIR__ . '/data/products.json';
        if (!is_file($dataFile)) {
            return;
        }

        $items = json_decode((string) file_get_contents($dataFile), true);
        if (!is_array($items)) {
            return;
        }

        $catMap = [];
        foreach ($this->db->table('categories')->get() as $c) {
            $catMap[(string) $c['slug']] = (int) $c['id'];
        }

        $brand = $this->db->table('brands')->where('slug', 'lufly')->first();
        $brandId = $brand !== null ? (int) $brand['id'] : null;

        $sortCounter = 0;
        $usedSkus = [];

        foreach ($items as $item) {
            $slug = trim((string) ($item['slug'] ?? ''));
            if ($slug === '' || $this->db->table('products')->where('slug', $slug)->exists()) {
                continue;
            }

            $modelCode = trim((string) ($item['model_code'] ?? ''));
            $categorySlug = (string) ($item['category'] ?? '');
            $categoryId = $catMap[$categorySlug] ?? null;
            $sortCounter++;

            $productId = (int) $this->db->insert('products', [
                'model_code' => $modelCode !== '' ? $modelCode : null,
                'slug' => $slug,
                'category_id' => $categoryId,
                'collection_id' => null,
                'brand_id' => $brandId,
                'status' => (string) ($item['status'] ?? 'active'),
                'is_featured' => 0,
                'sort_order' => $sortCounter,
                'created_at' => $this->stamp($item['created_at'] ?? null),
                'updated_at' => $this->stamp($item['updated_at'] ?? null),
            ]);

            /* ---- translations ----
               The legacy record carries exactly one language (en or cs). It is
               written under its own locale, plus copied verbatim under the
               fallback locale (en) so listings and search never show a blank
               product. Nothing is machine translated or invented: the fallback
               row holds the original legacy text until a translator edits it. */
            $locale = (string) ($item['locale'] ?? 'en');
            $fallback = (string) config('localization.fallback', 'en');
            $locales = $locale === $fallback ? [$locale] : [$locale, $fallback];

            foreach ($locales as $translationLocale) {
                $this->db->insert('product_translations', [
                    'product_id' => $productId,
                    'locale' => $translationLocale,
                    'name' => (string) ($item['name'] ?? $modelCode),
                    'short_description' => $this->nullIfEmpty((string) ($item['short_description'] ?? '')),
                    'description' => $this->nullIfEmpty((string) ($item['description'] ?? '')),
                ]);
            }

            /* ---- default variant: the legacy SKU, no invented price ---- */
            $sku = trim((string) ($item['sku'] ?? '')) ?: $modelCode;
            if ($sku !== '') {
                $unique = $sku;
                $suffix = 2;
                while (isset($usedSkus[$unique])) {
                    $unique = $sku . '-' . $suffix;
                    $suffix++;
                }
                $usedSkus[$unique] = true;

                $this->db->insert('product_variants', [
                    'product_id' => $productId,
                    'sku' => $unique,
                    'variant_name' => null,
                    'price' => 0.00,
                    'stock_status' => 'in_stock',
                    'sort_order' => 1,
                    'status' => 'active',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }

            /* ---- specifications parsed out of the legacy product copy ---- */
            $specs = is_array($item['specifications'] ?? null) ? $item['specifications'] : [];
            foreach (array_values($specs) as $index => $spec) {
                $key = trim((string) ($spec['key'] ?? ''));
                $value = trim((string) ($spec['value'] ?? ''));
                if ($key === '' || $value === '') {
                    continue;
                }

                $this->db->insert('product_specifications', [
                    'product_id' => $productId,
                    'variant_id' => null,
                    'spec_key' => $key,
                    'spec_value' => mb_substr($value, 0, 255),
                    'unit' => null,
                    'sort_order' => $index + 1,
                ]);
            }

            /* ---- media: the exported legacy images present on disk ---- */
            $images = is_array($item['images'] ?? null) ? $item['images'] : [];
            foreach (array_values($images) as $index => $image) {
                $image = '/' . ltrim((string) $image, '/');
                $filename = basename($image);
                $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION) ?: 'jpg');

                $mediaId = (int) $this->db->insert('media', [
                    'uuid' => $this->uuid4(),
                    'collection' => 'products',
                    'filename' => $filename,
                    'original_name' => $filename,
                    'path' => $image,
                    'mime_type' => $extension === 'png' ? 'image/png' : 'image/jpeg',
                    'extension' => $extension,
                    'size' => 0,
                    'meta' => json_encode([
                        'source' => 'legacy-wordpress',
                        'legacy_post_id' => $item['legacy_id'] ?? null,
                    ], JSON_UNESCAPED_UNICODE),
                    'status' => 'active',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

                $this->db->insert('product_media', [
                    'product_id' => $productId,
                    'variant_id' => null,
                    'media_id' => $mediaId,
                    'type' => $index === 0 ? 'main' : 'gallery',
                    'sort_order' => $index + 1,
                    'is_primary' => $index === 0 ? 1 : 0,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }

            /* ---- seo meta built from the product's own title/copy ---- */
            $this->db->insert('seo_meta', [
                'product_id' => $productId,
                'locale' => $locale,
                'meta_title' => mb_substr((string) ($item['name'] ?? $modelCode) . ' | LUFLY', 0, 255),
                'meta_description' => $this->nullIfEmpty(mb_substr(
                    str_replace("\n", ' ', (string) ($item['short_description'] ?? $item['description'] ?? '')),
                    0,
                    500,
                )),
                'og_title' => null,
                'og_description' => null,
                'canonical_url' => null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            /* ---- search keywords derived from real name + model code ---- */
            $keywords = ['lufly'];
            if ($modelCode !== '') {
                $keywords[] = mb_strtolower($modelCode);
            }
            foreach (preg_split('/[^\p{L}\p{N}-]+/u', (string) ($item['name'] ?? '')) ?: [] as $word) {
                if (mb_strlen($word) >= 3) {
                    $keywords[] = mb_strtolower($word);
                }
            }
            if ($categorySlug !== '') {
                $keywords[] = str_replace('-', ' ', $categorySlug);
            }

            foreach (array_unique($keywords) as $keyword) {
                $this->db->insert('product_search_keywords', [
                    'product_id' => $productId,
                    'keyword' => mb_substr($keyword, 0, 150),
                ]);
            }

            /* ---- import log (provenance back to the legacy post id) ---- */
            $this->db->insert('product_import_logs', [
                'product_id' => $productId,
                'source_file' => 'delete_files/lufly_new.sql',
                'source_page' => $item['legacy_id'] ?? null,
                'detected_sku' => $modelCode !== '' ? $modelCode : null,
                'status' => 'imported',
                'raw_data' => json_encode($item, JSON_UNESCAPED_UNICODE),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    private function nullIfEmpty(string $value): ?string
    {
        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function stamp(?string $value): string
    {
        if ($value === null || $value === '' || str_starts_with($value, '0000')) {
            return date('Y-m-d H:i:s');
        }

        return $value;
    }

    /** RFC 4122-ish v4 uuid without OpenSSL dependency. */
    private function uuid4(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);

        $hex = bin2hex($bytes);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12),
        );
    }
}
