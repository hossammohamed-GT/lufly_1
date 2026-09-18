<?php

declare(strict_types=1);

namespace Database\Seeders;

use Core\Database\Seeding\Seeder;

/**
 * Catalog foundations: the LUFLY brand, marketing collections and the
 * reusable attribute dictionary (colors, materials, finishes, installation).
 */
class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedBrand();
        $this->seedCollections();
        $this->seedAttributes();
    }

    private function seedBrand(): void
    {
        $exists = $this->db->table('brands')->where('slug', 'lufly')->exists();
        if ($exists) {
            return;
        }

        $this->db->insert('brands', [
            'name' => 'LUFLY',
            'slug' => 'lufly',
            'logo' => '/images/logo.png',
            'country' => 'Turkey',
            'website' => 'https://www.lufly.tr',
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function seedCollections(): void
    {
        $collections = [
            [
                'slug' => 'signature',
                'image' => '/images/lifestyle/heroc-1.webp',
                'translations' => [
                    'en' => ['name' => 'Signature Collection', 'description' => 'The defining LUFLY silhouettes - rimless wall-hung ceramics with hygienic glaze.'],
                    'tr' => ['name' => 'Signature Koleksiyonu', 'description' => 'LUFLY imzasını taşıyan, hijyenik sırlı kanalsız asma seramikler.'],
                    'cs' => ['name' => 'Signature kolekce', 'description' => 'Definující siluety LUFLY - bezokrajové závěsné keramiky s hygienickou glazurou.'],
                ],
            ],
            [
                'slug' => 'elite',
                'image' => '/images/lifestyle/heroc-2.webp',
                'translations' => [
                    'en' => ['name' => 'Elite Collection', 'description' => 'Luxury bidets and designer washbasins for premium hospitality projects.'],
                    'tr' => ['name' => 'Elite Koleksiyonu', 'description' => 'Premium otel projeleri için lüks bidetler ve tasarım lavabolar.'],
                    'cs' => ['name' => 'Elite kolekce', 'description' => 'Luxusní bidety a designová umyvadla pro prémiové hotelové projekty.'],
                ],
            ],
            [
                'slug' => 'luxury',
                'image' => '/images/lifestyle/heroc-3.webp',
                'translations' => [
                    'en' => ['name' => 'Luxury Collection', 'description' => 'Vanities, mirrors and architectural ceramics for statement bathrooms.'],
                    'tr' => ['name' => 'Luxury Koleksiyonu', 'description' => 'Çarpıcı banyolar için banyo dolapları, aynalar ve mimari seramikler.'],
                    'cs' => ['name' => 'Luxury kolekce', 'description' => 'Koupelnové skříňky, zrcadla a architektonická keramika pro výrazné koupelny.'],
                ],
            ],
        ];

        foreach ($collections as $index => $collection) {
            $existing = $this->db->table('collections')->where('slug', $collection['slug'])->first();
            if ($existing) {
                continue;
            }

            $collectionId = (int) $this->db->insert('collections', [
                'slug' => $collection['slug'],
                'image' => $collection['image'],
                'status' => 'active',
                'sort_order' => $index + 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            foreach ($collection['translations'] as $locale => $fields) {
                $this->db->insert('collection_translations', [
                    'collection_id' => $collectionId,
                    'locale' => $locale,
                    'name' => $fields['name'],
                    'description' => $fields['description'],
                ]);
            }
        }
    }

    private function seedAttributes(): void
    {
        $attributes = [
            [
                'code' => 'color',
                'name' => 'Color',
                'type' => 'color',
                'options' => [
                    ['value' => 'White', 'color_hex' => '#f4f4ef'],
                    ['value' => 'Alpin White', 'color_hex' => '#ffffff'],
                    ['value' => 'Matt Black', 'color_hex' => '#1d1d1b'],
                    ['value' => 'Gold', 'color_hex' => '#c8a24a'],
                    ['value' => 'Chrome', 'color_hex' => '#c9cdd1'],
                    ['value' => 'Anthracite', 'color_hex' => '#3a3d40'],
                ],
            ],
            [
                'code' => 'material',
                'name' => 'Material',
                'type' => 'select',
                'options' => [
                    ['value' => 'Vitreous China', 'color_hex' => null],
                    ['value' => 'Premium Ceramic', 'color_hex' => null],
                    ['value' => 'Solid Brass', 'color_hex' => null],
                    ['value' => 'Solid Wood', 'color_hex' => null],
                ],
            ],
            [
                'code' => 'finish',
                'name' => 'Finish',
                'type' => 'select',
                'options' => [
                    ['value' => 'Glossy Hygienic Glaze', 'color_hex' => null],
                    ['value' => 'Matt', 'color_hex' => null],
                    ['value' => 'PVD Gold', 'color_hex' => null],
                    ['value' => 'PVD Gunmetal', 'color_hex' => null],
                ],
            ],
            [
                'code' => 'installation-type',
                'name' => 'Installation Type',
                'type' => 'select',
                'options' => [
                    ['value' => 'Wall Hung', 'color_hex' => null],
                    ['value' => 'Floor Standing', 'color_hex' => null],
                    ['value' => 'Countertop', 'color_hex' => null],
                    ['value' => 'Vanity', 'color_hex' => null],
                    ['value' => 'Wall Mounted', 'color_hex' => null],
                ],
            ],
            [
                'code' => 'flush-type',
                'name' => 'Flush Type',
                'type' => 'select',
                'options' => [
                    ['value' => 'Rimless', 'color_hex' => null],
                    ['value' => 'Dual Flush', 'color_hex' => null],
                    ['value' => 'Single Flush', 'color_hex' => null],
                ],
            ],
        ];

        foreach ($attributes as $attribute) {
            $existing = $this->db->table('attributes')->where('code', $attribute['code'])->first();
            if ($existing) {
                continue;
            }

            $attributeId = (int) $this->db->insert('attributes', [
                'code' => $attribute['code'],
                'name' => $attribute['name'],
                'type' => $attribute['type'],
                'is_filterable' => 1,
                'is_sortable' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            foreach ($attribute['options'] as $optionIndex => $option) {
                $this->db->insert('attribute_options', [
                    'attribute_id' => $attributeId,
                    'value' => $option['value'],
                    'color_hex' => $option['color_hex'],
                    'sort_order' => $optionIndex + 1,
                ]);
            }
        }
    }
}
