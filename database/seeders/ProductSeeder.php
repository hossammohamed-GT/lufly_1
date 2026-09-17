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
            $dataFile = '/home/vdta/Downloads/well-known/products.json';
        }

        if (!file_exists($dataFile)) {
            return;
        }

        $items = json_decode((string) file_get_contents($dataFile), true);
        if (!is_array($items)) {
            return;
        }

        // Map category names to DB IDs
        $categoryRows = $this->db->select('SELECT id, slug FROM categories');
        $catMap = [];
        foreach ($categoryRows as $row) {
            $catMap[$row['slug']] = (int) $row['id'];
        }

        $catSlugLookup = [
            'Wall-Hung Toilets' => 'wall-hung-toilets',
            'Luxury Bidets' => 'luxury-bidets',
            'Designer Washbasins' => 'designer-washbasins',
            'Vanity & Cabinets' => 'vanity-cabinets',
            'Architectural Ceramics' => 'architectural-ceramics',
        ];

        $catI18n = [
            'Wall-Hung Toilets' => [
                'en' => 'Wall-Hung Toilet',
                'tr' => 'Asma Klozet',
                'ar' => 'مرحاض معلق فاخر',
                'cs' => 'Závěsné WC',
            ],
            'Luxury Bidets' => [
                'en' => 'Luxury Bidet',
                'tr' => 'Lüks Bide',
                'ar' => 'بيديه وشطاف أوروبي',
                'cs' => 'Luxusní bidet',
            ],
            'Designer Washbasins' => [
                'en' => 'Designer Washbasin',
                'tr' => 'Tasarım Lavabo',
                'ar' => 'حوض مغسلة ديكوري',
                'cs' => 'Designové umyvadlo',
            ],
            'Vanity & Cabinets' => [
                'en' => 'Bathroom Vanity',
                'tr' => 'Banyo Dolabı',
                'ar' => 'خزانة حمام عصرية',
                'cs' => 'Koupelnová skříňka',
            ],
            'Architectural Ceramics' => [
                'en' => 'Architectural Ceramic',
                'tr' => 'Mimari Seramik',
                'ar' => 'سيراميك معماري فاخر',
                'cs' => 'Architektonická keramika',
            ],
        ];

        $seenSkus = [];

        foreach ($items as $item) {
            $rawSku = trim((string) ($item['sku'] ?? ''));
            if ($rawSku === '') {
                $rawSku = 'LUFLY-' . ($item['id'] ?? uniqid());
            }

            // Ensure SKU uniqueness
            if (isset($seenSkus[$rawSku])) {
                $sku = $rawSku . '-' . ($item['id'] ?? uniqid());
            } else {
                $sku = $rawSku;
            }
            $seenSkus[$sku] = true;

            $slug = 'lufly-' . strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $rawSku)) . '-' . ($item['id'] ?? uniqid());
            $catName = $item['category'] ?? 'Wall-Hung Toilets';
            $catSlug = $catSlugLookup[$catName] ?? 'wall-hung-toilets';
            $categoryId = $catMap[$catSlug] ?? ($catMap['wall-hung-toilets'] ?? null);

            $image = $item['image'] ?? '/images/products/prod_146_1620-111-a.jpg';
            $specs = $item['specs'] ?? [
                'material' => 'Vitreous China / Premium Ceramic',
                'origin' => 'Manufactured in Turkey / European Standards',
                'finish' => 'Glossy Hygienic Glaze',
                'warranty' => '10 Years Factory Guarantee',
            ];

            $price = (float) ($item['price'] ?? 0.0);

            // Check if already seeded
            $existing = $this->db->table('products')->where('sku', $sku)->first();
            if ($existing) {
                $productId = (int) $existing['id'];
            } else {
                $productId = (int) $this->db->insert('products', [
                    'category_id' => $categoryId,
                    'sku' => $sku,
                    'slug' => $slug,
                    'price' => $price,
                    'image' => $image,
                    'specs' => json_encode($specs, JSON_UNESCAPED_UNICODE),
                    'status' => 'active',
                    'seo' => json_encode([
                        'title' => 'LUFLY ' . ($catI18n[$catName]['en'] ?? 'Sanitary') . ' ' . $sku,
                        'description' => 'Official European export model LUFLY ' . $sku . '. Vitreous china sanitary ware.',
                    ], JSON_UNESCAPED_UNICODE),
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }

            // Build translations for en, tr, ar, cs
            $i18n = $catI18n[$catName] ?? [
                'en' => 'Sanitary Ware',
                'tr' => 'Sıhhi Tesisat',
                'ar' => 'خزف صحي',
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
                'ar' => [
                    'name' => 'لوفلي ' . $i18n['ar'] . ' (كود: ' . $rawSku . ')',
                    'short_description' => 'خزف صحي تركي فاخر فائق الجودة مطابق للمواصفات القياسية الأوروبية مع ضمان مصنعي لمدة 10 سنوات.',
                    'description' => 'مواصفات تصدير معتمدة لـ ' . $i18n['ar'] . ' من لوفلي (كود المنتج: ' . $rawSku . '). مصنع من السيراميك الزجاجي Vitreous China بأعلى معايير المتانة مع طلاء فائق النعومة ومضاد للبكتيريا.',
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
