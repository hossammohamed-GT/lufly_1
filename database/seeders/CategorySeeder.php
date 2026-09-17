<?php

declare(strict_types=1);

namespace Database\Seeders;

use Core\Database\Seeding\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'slug' => 'wall-hung-toilets',
                'image' => '/images/products/prod_146_1620-111-a.jpg',
                'translations' => [
                    'en' => [
                        'name' => 'Wall-Hung Toilets',
                        'description' => 'Premium European rimless wall-hung ceramic toilets with hygienic antibacterial glaze.',
                    ],
                    'tr' => [
                        'name' => 'Asma Klozetler',
                        'description' => 'Hijyenik sırlı, kanalsız modern asma klozet ve rezervuar sistemleri.',
                    ],
                    'ar' => [
                        'name' => 'مراحيض معلقة فاخرة',
                        'description' => 'مراحيض معلقة بتصميم أوروبي عصري خالية من الحواف مع طلاء زجاجي مقاوم للبكتيريا.',
                    ],
                    'cs' => [
                        'name' => 'Závěsná WC',
                        'description' => 'Prémiová bezokrajová závěsná keramická WC s hygienickou antibakteriální glazurou.',
                    ],
                ],
            ],
            [
                'slug' => 'luxury-bidets',
                'image' => '/images/products/prod_167_1650-081.jpg',
                'translations' => [
                    'en' => [
                        'name' => 'Luxury Bidets',
                        'description' => 'Architectural wall-hung and floor-standing European sanitary bidets.',
                    ],
                    'tr' => [
                        'name' => 'Lüks Bidetler',
                        'description' => 'Modern mimari asma ve ayaklı bide serileri.',
                    ],
                    'ar' => [
                        'name' => 'شطافات وبيديهات أوروبية',
                        'description' => 'بيديهات وشطافات معلقة وأرضية مصممة بأعلى معايير الجودة الأوروبية.',
                    ],
                    'cs' => [
                        'name' => 'Luxusní bidety',
                        'description' => 'Architektonické závěsné a stojící evropské bidety.',
                    ],
                ],
            ],
            [
                'slug' => 'designer-washbasins',
                'image' => '/images/products/prod_180_1610-242-65.jpg',
                'translations' => [
                    'en' => [
                        'name' => 'Designer Washbasins',
                        'description' => 'Countertop, vanity and wall-mounted luxury vitreous china washbasins.',
                    ],
                    'tr' => [
                        'name' => 'Tasarım Lavabolar',
                        'description' => 'Tezgah üstü, çanak ve duvara monte lüks seramik lavabolar.',
                    ],
                    'ar' => [
                        'name' => 'أحواض مغاسل ديكورية',
                        'description' => 'أحواض مغاسل سيراميك فوق الرخام وجدارية بتشطيبات وتصاميم عصرية راقية.',
                    ],
                    'cs' => [
                        'name' => 'Designová umyvadla',
                        'description' => 'Umyvadla na desku a nábytková luxusní keramická umyvadla.',
                    ],
                ],
            ],
            [
                'slug' => 'vanity-cabinets',
                'image' => '/images/products/prod_204_1610-242.jpg',
                'translations' => [
                    'en' => [
                        'name' => 'Vanity & Cabinets',
                        'description' => 'Moisture-resistant luxury bathroom vanities, mirrors, and modular furniture.',
                    ],
                    'tr' => [
                        'name' => 'Banyo Dolapları',
                        'description' => 'Suya ve neme dayanıklı lüks banyo dolapları ve ayna modülleri.',
                    ],
                    'ar' => [
                        'name' => 'خزائن ووحدات حمام',
                        'description' => 'وحدات وخزائن حمام فخمة مقاومة للرطوبة والمياه بتصاميم تركية متطورة.',
                    ],
                    'cs' => [
                        'name' => 'Koupelnové skříňky',
                        'description' => 'Koupelnový nábytek a skříňky vysoce odolné proti vlhkosti.',
                    ],
                ],
            ],
            [
                'slug' => 'architectural-ceramics',
                'image' => '/images/products/prod_2050_1690-000.jpg',
                'translations' => [
                    'en' => [
                        'name' => 'Architectural Ceramics',
                        'description' => 'High-durability commercial and residential vitrified ceramic surfaces and tiles.',
                    ],
                    'tr' => [
                        'name' => 'Mimari Seramikler',
                        'description' => 'Yüksek dayanımlı ticari ve konut mimari seramik yüzeyler ve aksesuarlar.',
                    ],
                    'ar' => [
                        'name' => 'سيراميك وبورسلين معماري',
                        'description' => 'بلاط وسيراميك وأسطح خزفية معمارية للمشاريع الكبرى والمجمعات السكنية.',
                    ],
                    'cs' => [
                        'name' => 'Architektonická keramika',
                        'description' => 'Vysoce odolné keramické povrchy a doplňky pro rezidenční i komerční projekty.',
                    ],
                ],
            ],
        ];

        foreach ($categories as $cat) {
            $existing = $this->db->table('categories')->where('slug', $cat['slug'])->first();
            if ($existing) {
                $categoryId = (int) $existing['id'];
            } else {
                $categoryId = (int) $this->db->insert('categories', [
                    'slug' => $cat['slug'],
                    'image' => $cat['image'],
                    'status' => 'active',
                    'seo' => json_encode(['title' => $cat['translations']['en']['name']], JSON_UNESCAPED_UNICODE),
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }

            foreach ($cat['translations'] as $locale => $trans) {
                if ($this->db->table('category_translations')->where('category_id', $categoryId)->where('locale', $locale)->exists()) {
                    continue;
                }

                $this->db->insert('category_translations', [
                    'category_id' => $categoryId,
                    'locale' => $locale,
                    'name' => $trans['name'],
                    'description' => $trans['description'],
                ]);
            }
        }
    }
}
