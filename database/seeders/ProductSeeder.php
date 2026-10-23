<?php

declare(strict_types=1);

namespace Database\Seeders;

use Core\Database\Seeding\Seeder;

/**
 * Imports the real LUFLY catalog exported by the separate catalog manager.
 *
 * Runtime source of truth: database/seeders/data/products.json. Product and
 * media paths in that file are self-contained, so reseeding never reads the
 * manager directory or its SQL export.
 *
 * Source descriptions, codes, category assignments, media ordering and
 * provenance are preserved. Missing descriptions, specifications and
 * translations stay empty; generated slugs and duplicate-SKU suffixes are
 * technical identifiers only. Unavailable media stays visible to Admin as
 * missing/hidden records without becoming broken storefront images.
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
        foreach ($this->db->table('product_variants')->get() as $variant) {
            $usedSkus[(string) $variant['sku']] = true;
        }

        foreach ($items as $item) {
            $slug = trim((string) ($item['slug'] ?? ''));
            if ($slug === '' || $this->db->table('products')->where('slug', $slug)->exists()) {
                continue;
            }

            $modelCode = trim((string) ($item['model_code'] ?? ''));
            $categorySlug = (string) ($item['category'] ?? '');
            $categoryId = $catMap[$categorySlug] ?? null;
            $sortCounter++;
            $managerId = (int) ($item['manager_id'] ?? 0);
            $name = trim((string) ($item['name'] ?? ''));
            if ($name === '') {
                $name = $modelCode !== '' ? $modelCode : 'LUFLY #' . ($managerId > 0 ? $managerId : $sortCounter);
            }

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
               The manager stores one unlocalised text field. Its original copy
               is stored under the supplied/fallback locale without machine
               translation; additional locales remain for translators in Admin. */
            $locale = (string) ($item['locale'] ?? 'en');
            $fallback = (string) config('localization.fallback', 'en');
            $locales = $locale === $fallback ? [$locale] : [$locale, $fallback];

            foreach ($locales as $translationLocale) {
                $this->db->insert('product_translations', [
                    'product_id' => $productId,
                    'locale' => $translationLocale,
                    'name' => $name,
                    'short_description' => $this->nullIfEmpty((string) ($item['short_description'] ?? '')),
                    'description' => $this->nullIfEmpty((string) ($item['description'] ?? '')),
                ]);
            }

            /* ---- default variant: the manager SKU, no invented price ---- */
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

            /* ---- structured specifications, when present in the source ---- */
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

            /* ---- media ----
               Preserve every attachment in source order. Web previews use only
               available, previewable files; EMF/source-only attachments stay
               hidden and unavailable files stay missing for Admin recovery. */
            $images = is_array($item['images'] ?? null) ? $item['images'] : [];
            $sortOrder = 0;
            $hasPrimary = false;

            foreach ($images as $image) {
                if (!is_array($image)) {
                    continue;
                }

                $path = '/' . ltrim((string) ($image['path'] ?? ''), '/');
                if ($path === '/') {
                    continue;
                }

                $type = (string) ($image['type'] ?? 'gallery');
                $available = (bool) ($image['available'] ?? false);
                $previewable = (bool) ($image['previewable'] ?? true);
                $filename = basename($path);
                $extension = strtolower((string) ($image['extension'] ?? pathinfo($filename, PATHINFO_EXTENSION)));
                $originalName = trim((string) ($image['original_name'] ?? ''));
                $sortOrder++;

                /* The primary flag only goes to a web-previewable product photo. */
                $isPrimary = !$hasPrimary && $available && $previewable && $type !== 'drawing';
                if ($isPrimary) {
                    $hasPrimary = true;
                }

                $mediaId = (int) $this->db->insert('media', [
                    'uuid' => $this->uuid4(),
                    'collection' => $categorySlug !== '' ? $categorySlug : 'products',
                    'filename' => $filename,
                    'original_name' => $originalName !== '' ? $originalName : $filename,
                    'path' => $path,
                    'mime_type' => (string) ($image['mime_type'] ?? 'application/octet-stream'),
                    'extension' => $extension,
                    'size' => (int) ($image['size'] ?? 0),
                    'width' => isset($image['width']) ? (int) $image['width'] : null,
                    'height' => isset($image['height']) ? (int) $image['height'] : null,
                    'meta' => json_encode([
                        'source' => 'lufly-manager',
                        'manager_product_id' => $managerId > 0 ? $managerId : null,
                        'manager_media_id' => $image['manager_id'] ?? null,
                        'original_path' => $image['source_path'] ?? null,
                        'source_column' => $image['source_column'] ?? null,
                        'source_row' => $image['source_row'] ?? null,
                        'source_sort_order' => $image['source_sort_order'] ?? null,
                        'source_sha256' => $image['source_sha256'] ?? null,
                        'sha256' => $image['sha256'] ?? null,
                        'caption' => $image['caption'] ?? null,
                        'previewable' => $previewable,
                    ], JSON_UNESCAPED_UNICODE),
                    'owner_id' => null,
                    'status' => !$available ? 'missing' : ($previewable ? 'active' : 'hidden'),
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

                $this->db->insert('product_media', [
                    'product_id' => $productId,
                    'variant_id' => null,
                    'media_id' => $mediaId,
                    'type' => $type,
                    'sort_order' => $sortOrder,
                    'is_primary' => $isPrimary ? 1 : 0,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }

            /* ---- seo meta built from the product's own title/copy ---- */
            $this->db->insert('seo_meta', [
                'product_id' => $productId,
                'locale' => $locale,
                'meta_title' => mb_substr($name . ' | LUFLY', 0, 255),
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
            foreach (preg_split('/[^\p{L}\p{N}-]+/u', $name) ?: [] as $word) {
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

            /* ---- import log (provenance back to the manager row) ---- */
            $this->db->insert('product_import_logs', [
                'product_id' => $productId,
                'source_file' => (string) ($item['source_file'] ?? 'lufly_catalog.sql'),
                'source_page' => $item['source_row'] ?? null,
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
