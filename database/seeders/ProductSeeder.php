<?php

declare(strict_types=1);

namespace Database\Seeders;

use Core\Database\Seeding\Seeder;

/**
 * Imports the legacy product data (database/seeders/data/products.json)
 * into the new product architecture:
 *
 * products -> translations, default variant, specifications, dimensions,
 * attribute values, media, seo meta, search keywords and an import log row
 * so the provenance of every row stays traceable.
 */
class ProductSeeder extends Seeder
{
    /** sku prefix -> category slug (list of pairs: avoids PHP int-key casts) */
    private const SKU_CATEGORY_MAP = [
        ['1620', 'wall-hung-toilets'],
        ['1650', 'luxury-bidets'],
        ['1610', 'designer-washbasins'],
        ['1660', 'vanity-cabinets'],
        ['1685', 'designer-washbasins'],
        ['1690', 'architectural-ceramics'],
        ['1654', 'designer-washbasins'],
        ['1623', 'wall-hung-toilets'],
        ['1501', 'luxury-bidets'],
        ['1502', 'luxury-bidets'],
        ['1503', 'luxury-bidets'],
        ['1651', 'designer-washbasins'],
        ['1653', 'designer-washbasins'],
        ['1659', 'designer-washbasins'],
        ['4311', 'vanity-cabinets'],
        ['9243', 'vanity-cabinets'],
        ['DL-01', 'designer-washbasins'],
        ['DL-05', 'vanity-cabinets'],
        ['DL-08', 'designer-washbasins'],
        ['PE-1', 'architectural-ceramics'],
        ['PE-3', 'architectural-ceramics'],
    ];

    private const CATEGORY_I18N = [
        'wall-hung-toilets' => [
            'en' => 'Wall-Hung Toilet',
            'tr' => 'Asma Klozet',
            'cs' => 'Závěsné WC',
            'installation' => 'Wall Hung',
        ],
        'luxury-bidets' => [
            'en' => 'Luxury Bidet',
            'tr' => 'Lüks Bide',
            'cs' => 'Luxusní bidet',
            'installation' => 'Wall Hung',
        ],
        'designer-washbasins' => [
            'en' => 'Designer Washbasin',
            'tr' => 'Tasarım Lavabo',
            'cs' => 'Designové umyvadlo',
            'installation' => 'Countertop',
        ],
        'vanity-cabinets' => [
            'en' => 'Bathroom Vanity',
            'tr' => 'Banyo Dolabı',
            'cs' => 'Koupelnová skříňka',
            'installation' => 'Vanity',
        ],
        'architectural-ceramics' => [
            'en' => 'Architectural Ceramic',
            'tr' => 'Mimari Seramik',
            'cs' => 'Architektonická keramika',
            'installation' => 'Wall Mounted',
        ],
    ];

    public function run(): void
    {
        $dataFile = __DIR__ . '/data/products.json';
        if (!file_exists($dataFile)) {
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

        $attributeIds = [];
        foreach ($this->db->table('attributes')->get() as $a) {
            $attributeIds[(string) $a['code']] = (int) $a['id'];
        }

        $optionByKey = [];
        foreach ($this->db->table('attribute_options')->orderBy('sort_order', 'asc')->get() as $o) {
            $optionByKey[(int) $o['attribute_id'] . '|' . (string) $o['value']] = (int) $o['id'];
        }

        $seenSkus = [];
        $featuredBudget = 8;
        $sortCounter = 0;

        foreach ($items as $item) {
            $modelCode = trim((string) ($item['sku'] ?? ''));
            if ($modelCode === '') {
                $modelCode = 'LUFLY-' . ($item['id'] ?? uniqid());
            }

            if (isset($seenSkus[$modelCode])) {
                continue;
            }
            $seenSkus[$modelCode] = true;

            $categorySlug = $this->categorySlugFor($modelCode);
            $categoryId = $catMap[$categorySlug] ?? ($catMap['wall-hung-toilets'] ?? null);
            $i18n = self::CATEGORY_I18N[$categorySlug] ?? self::CATEGORY_I18N['wall-hung-toilets'];

            $slug = trim('lufly-' . strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $modelCode) ?? ''), '-');
            if ($this->db->table('products')->where('slug', $slug)->exists()) {
                continue;
            }

            $isFeatured = $featuredBudget > 0;
            if ($isFeatured) {
                $featuredBudget--;
            }
            $sortCounter++;

            $productId = (int) $this->db->insert('products', [
                'model_code' => $modelCode,
                'slug' => $slug,
                'category_id' => $categoryId,
                'brand_id' => $brandId,
                'status' => 'active',
                'is_featured' => $isFeatured ? 1 : 0,
                'sort_order' => $sortCounter,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            /* ---- translations ---- */
            $translations = [
                'en' => [
                    'name' => 'LUFLY ' . $i18n['en'] . ' ' . $modelCode,
                    'short_description' => 'Premium Turkish vitreous china ' . strtolower($i18n['en']) . ' with antibacterial hygienic glaze and 10-year factory warranty.',
                    'description' => 'Architectural specification for LUFLY ' . $i18n['en'] . ' (Model: ' . $modelCode . '). Manufactured in Gaziantep, Turkey to strict European EN and CE standards. Material: Vitreous China, 10-Year Guarantee.',
                ],
                'tr' => [
                    'name' => 'LUFLY ' . $i18n['tr'] . ' ' . $modelCode,
                    'short_description' => 'Yüksek kaliteli hijyenik sırlı ' . mb_strtolower($i18n['tr']) . ' (Kod: ' . $modelCode . '). 10 yıl fabrika garantili.',
                    'description' => 'LUFLY ' . $i18n['tr'] . ' serisi (Kod: ' . $modelCode . '). Gaziantep üretim tesislerimizde Avrupa EN-997 ve CE standartlarına uygun olarak üretilmiştir.',
                ],
                'cs' => [
                    'name' => 'LUFLY ' . $i18n['cs'] . ' ' . $modelCode,
                    'short_description' => 'Prémiová turecká sanitární keramika ' . mb_strtolower($i18n['cs']) . ' s hygienickou glazurou a 10letou zárukou.',
                    'description' => 'Architektonická specifikace LUFLY ' . $i18n['cs'] . ' (model ' . $modelCode . '). Vyrobeno v Gaziantepu v Turecku podle evropských norem EN a CE. Materiál: vitrážová keramika, 10letá záruka.',
                ],
            ];

            foreach ($translations as $locale => $fields) {
                $this->db->insert('product_translations', [
                    'product_id' => $productId,
                    'locale' => $locale,
                    'name' => $fields['name'],
                    'short_description' => $fields['short_description'],
                    'description' => $fields['description'],
                ]);
            }

            /* ---- default variant (the sellable copy) ---- */
            $variantId = (int) $this->db->insert('product_variants', [
                'product_id' => $productId,
                'sku' => $modelCode,
                'variant_name' => null,
                'price' => 0.00,
                'stock_status' => 'in_stock',
                'sort_order' => 1,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            /* ---- flexible specifications ---- */
            $specs = [
                ['spec_key' => 'Material', 'spec_value' => 'Vitreous China / Premium Ceramic', 'unit' => null],
                ['spec_key' => 'Origin', 'spec_value' => 'Gaziantep, Turkey', 'unit' => null],
                ['spec_key' => 'Finish', 'spec_value' => 'Glossy Hygienic Glaze', 'unit' => null],
                ['spec_key' => 'Warranty', 'spec_value' => '10 Years Factory Guarantee', 'unit' => null],
                ['spec_key' => 'Standards', 'spec_value' => 'EN 997 / CE Certified', 'unit' => null],
            ];

            foreach ($specs as $specIndex => $spec) {
                $this->db->insert('product_specifications', [
                    'product_id' => $productId,
                    'variant_id' => null,
                    'spec_key' => $spec['spec_key'],
                    'spec_value' => $spec['spec_value'],
                    'unit' => $spec['unit'],
                    'sort_order' => $specIndex + 1,
                ]);
            }

            /* ---- attribute values (Color / Material / Finish / Installation) ---- */
            $this->attachAttribute($attributeIds, $optionByKey, $productId, $variantId, 'material', 'Vitreous China');
            $this->attachAttribute($attributeIds, $optionByKey, $productId, $variantId, 'finish', 'Glossy Hygienic Glaze');
            $this->attachAttribute($attributeIds, $optionByKey, $productId, $variantId, 'color', 'White');
            $this->attachAttribute($attributeIds, $optionByKey, $productId, $variantId, 'installation-type', (string) $i18n['installation']);
            if ($categorySlug === 'wall-hung-toilets' || $categorySlug === 'luxury-bidets') {
                $this->attachAttribute($attributeIds, $optionByKey, $productId, $variantId, 'flush-type', 'Rimless');
            }

            /* ---- media (library row + product attachment) ---- */
            $image = $item['image'] ?? null;
            if (is_string($image) && $image !== '') {
                $image = '/' . ltrim($image, '/');
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
                    'meta' => json_encode(['source' => 'legacy-catalog'], JSON_UNESCAPED_UNICODE),
                    'status' => 'active',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

                $this->db->insert('product_media', [
                    'product_id' => $productId,
                    'variant_id' => null,
                    'media_id' => $mediaId,
                    'type' => 'thumbnail',
                    'sort_order' => 1,
                    'is_primary' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }

            /* ---- seo meta (en) ---- */
            $this->db->insert('seo_meta', [
                'product_id' => $productId,
                'locale' => 'en',
                'meta_title' => 'LUFLY ' . $i18n['en'] . ' ' . $modelCode . ' | LUFLY Sanitary Ware',
                'meta_description' => $translations['en']['short_description'],
                'og_title' => null,
                'og_description' => null,
                'canonical_url' => null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            /* ---- search keywords ---- */
            $keywords = array_unique(array_filter([
                'LUFLY',
                $modelCode,
                $i18n['en'],
                self::CATEGORY_I18N[$categorySlug]['en'] ?? null,
                'Sanitary Ware',
                'Bathroom',
                $categorySlug === 'wall-hung-toilets' ? 'WC' : null,
                $categorySlug === 'wall-hung-toilets' ? 'Toilet' : null,
            ]));

            foreach ($keywords as $keyword) {
                $this->db->insert('product_search_keywords', [
                    'product_id' => $productId,
                    'keyword' => mb_strtolower((string) $keyword),
                ]);
            }

            /* ---- import log (provenance) ---- */
            $this->db->insert('product_import_logs', [
                'product_id' => $productId,
                'source_file' => 'database/seeders/data/products.json',
                'source_page' => null,
                'detected_sku' => $modelCode,
                'status' => 'imported',
                'raw_data' => json_encode($item, JSON_UNESCAPED_UNICODE),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    /** @param array<string, int> $attributeIds @param array<string, int> $optionByKey */
    private function attachAttribute(
        array $attributeIds,
        array $optionByKey,
        int $productId,
        int $variantId,
        string $attributeCode,
        string $optionValue,
    ): void {
        $attributeId = $attributeIds[$attributeCode] ?? null;
        if ($attributeId === null) {
            return;
        }

        $optionId = $optionByKey[$attributeId . '|' . $optionValue] ?? null;
        if ($optionId === null) {
            return;
        }

        $this->db->insert('product_attribute_values', [
            'product_id' => $productId,
            'variant_id' => $variantId,
            'attribute_id' => $attributeId,
            'attribute_option_id' => $optionId,
            'custom_value' => null,
        ]);
    }

    private function categorySlugFor(string $sku): string
    {
        foreach (self::SKU_CATEGORY_MAP as [$prefix, $slug]) {
            if (str_starts_with($sku, $prefix)) {
                return $slug;
            }
        }

        return 'wall-hung-toilets';
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
