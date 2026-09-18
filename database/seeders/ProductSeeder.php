<?php

declare(strict_types=1);

namespace Database\Seeders;

use Core\Database\Seeding\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $dataFile = __DIR__ . '/data/products.json';
        if (!file_exists($dataFile)) {
            return;
        }

        $json = file_get_contents($dataFile);
        $items = json_decode($json, true);
        if (!is_array($items)) {
            return;
        }

        $categories = $this->db->table('categories')->get();
        $catMap = [];
        foreach ($categories as $c) {
            $catMap[$c['slug']] = $c['id'];
        }

        $skuToCategory = [
            '1620' => $catMap['wall-hung-toilets'] ?? null,
            '1650' => $catMap['luxury-bidets'] ?? null,
            '1610' => $catMap['designer-washbasins'] ?? null,
            '1660' => $catMap['vanity-cabinets'] ?? null,
            '1685' => $catMap['designer-washbasins'] ?? null,
            '1690' => $catMap['architectural-ceramics'] ?? null,
            '1654' => $catMap['designer-washbasins'] ?? null,
            '1623' => $catMap['wall-hung-toilets'] ?? null,
            '1501' => $catMap['luxury-bidets'] ?? null,
            '1502' => $catMap['luxury-bidets'] ?? null,
            '1503' => $catMap['luxury-bidets'] ?? null,
            '1651' => $catMap['designer-washbasins'] ?? null,
            '1653' => $catMap['designer-washbasins'] ?? null,
            '1659' => $catMap['designer-washbasins'] ?? null,
            '4311' => $catMap['vanity-cabinets'] ?? null,
            '9243' => $catMap['vanity-cabinets'] ?? null,
            'DL-01' => $catMap['designer-washbasins'] ?? null,
            'DL-05' => $catMap['vanity-cabinets'] ?? null,
            'DL-08' => $catMap['designer-washbasins'] ?? null,
            'PE-1' => $catMap['architectural-ceramics'] ?? null,
            'PE-3' => $catMap['architectural-ceramics'] ?? null,
        ];

        $catI18n = [
            'Wall-Hung Toilets' => [
                'en' => 'Wall-Hung Toilet',
                'tr' => 'Asma Klozet',
                'cs' => 'Závěsné WC',
            ],
            'Luxury Bidets' => [
                'en' => 'Luxury Bidet',
                'tr' => 'Lüks Bide',
                'cs' => 'Luxusní bidet',
            ],
            'Designer Washbasins' => [
                'en' => 'Designer Washbasin',
                'tr' => 'Tasarım Lavabo',
                'cs' => 'Designové umyvadlo',
            ],
            'Vanity & Cabinets' => [
                'en' => 'Bathroom Vanity',
                'tr' => 'Banyo Dolabı',
                'cs' => 'Koupelnová skříňka',
            ],
            'Architectural Ceramics' => [
                'en' => 'Architectural Ceramic',
                'tr' => 'Mimari Seramik',
                'cs' => 'Architektonická keramika',
            ],
        ];

        $seenSkus = [];

        foreach ($items as $item) {
            $rawSku = trim((string) ($item['sku'] ?? ''));
            if ($rawSku === '') {
                $rawSku = 'LUFLY-' . ($item['id'] ?? uniqid());
            }

            if (isset($seenSkus[$rawSku])) {
                continue;
            }
            $seenSkus[$rawSku] = true;

            $image = $item['image'] ?? null;
            if ($image) {
                $image = '/' . ltrim((string) $image, '/');
            }

            $catId = null;
            $matchedCatName = null;
            foreach ($skuToCategory as $prefix => $targetCatId) {
                if (str_starts_with($rawSku, $prefix)) {
                    $catId = $targetCatId;
                    break;
                }
            }

            if (!$catId) {
                $catId = $catMap['wall-hung-toilets'] ?? 1;
            }

            foreach ($categories as $c) {
                if ($c['id'] == $catId) {
                    $matchedCatName = $c['slug'];
                    break;
                }
            }

            $baseSlug = 'lufly-' . strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $rawSku));
            $baseSlug = trim($baseSlug, '-');

            $existing = $this->db->table('products')->where('sku', $rawSku)->first();
            if ($existing) {
                $productId = (int) $existing['id'];
            } else {
                $productId = (int) $this->db->insert('products', [
                    'category_id' => $catId,
                    'sku' => $rawSku,
                    'slug' => $baseSlug,
                    'image' => $image,
                    'price' => 0.00,
                    'status' => 'active',
                    'specs' => json_encode([
                        'material' => 'Vitreous China / Premium Ceramic',
                        'warranty' => '10 Years Factory Guarantee',
                        'standards' => 'EN 997 / CE Standard Certified',
                        'origin' => 'Gaziantep, Turkey',
                    ], JSON_UNESCAPED_UNICODE),
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }

            $catNameReadable = 'Sanitary Ware';
            if ($matchedCatName === 'wall-hung-toilets') $catNameReadable = 'Wall-Hung Toilets';
            elseif ($matchedCatName === 'luxury-bidets') $catNameReadable = 'Luxury Bidets';
            elseif ($matchedCatName === 'designer-washbasins') $catNameReadable = 'Designer Washbasins';
            elseif ($matchedCatName === 'vanity-cabinets') $catNameReadable = 'Vanity & Cabinets';
            elseif ($matchedCatName === 'architectural-ceramics') $catNameReadable = 'Architectural Ceramics';

            $i18n = $catI18n[$catNameReadable] ?? [
                'en' => 'Sanitary Ware',
                'tr' => 'Sıhhi Tesisat',
                'cs' => 'Sanitární keramika',
            ];

            $translations = [
                'en' => [
                    'name' => 'LUFLY ' . $i18n['en'] . ' ' . $rawSku,
                    'short_description' => 'Premium Turkish Vitreous China ' . strtolower($i18n['en']) . ' with antibacterial hygienic glaze and 10-year factory warranty.',
                    'description' => 'Architectural specification for LUFLY ' . $i18n['en'] . ' (Model SKU: ' . $rawSku . '). Manufactured in Gaziantep, Turkey to strict European EN and CE standards. Material: Vitreous China, 10-Year Guarantee.',
                ],
                'tr' => [
                    'name' => 'LUFLY ' . $i18n['tr'] . ' ' . $rawSku,
                    'short_description' => 'Yüksek kaliteli hijyenik sırlı ' . mb_strtolower($i18n['tr']) . ' (Kod: ' . $rawSku . '). 10 yıl fabrika garantili.',
                    'description' => 'LUFLY ' . $i18n['tr'] . ' serisi (Kod: ' . $rawSku . '). Gaziantep üretim tesislerimizde Avrupa EN-997 ve CE standartlarına uygun olarak üretilmiştir.',
                ],
                'cs' => [
                    'name' => 'LUFLY ' . $i18n['cs'] . ' ' . $rawSku,
                    'short_description' => 'Prémiová turecká sanitární keramika ' . mb_strtolower($i18n['cs']) . ' s hygienickou glazurou a 10letou zárukou.',
                    'description' => strip_tags((string) ($item['full_description'] ?? $item['description'] ?? ('Kód: ' . $rawSku . ' ' . $i18n['cs']))),
                ],
            ];

            foreach ($translations as $locale => $fields) {
                if ($this->db->table('product_translations')->where('product_id', $productId)->where('locale', $locale)->exists()) {
                    continue;
                }

                $this->db->insert('product_translations', [
                    'product_id' => $productId,
                    'locale' => $locale,
                    'name' => $fields['name'],
                    'short_description' => $fields['short_description'],
                    'description' => $fields['description'],
                ]);
            }
        }
    }
}
