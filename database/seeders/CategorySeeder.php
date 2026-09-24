<?php

declare(strict_types=1);

namespace Database\Seeders;

use Core\Database\Seeding\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $file = __DIR__ . '/data/categories.json';
        if (!is_file($file)) {
            return;
        }

        $categories = json_decode((string) file_get_contents($file), true);
        if (!is_array($categories)) {
            return;
        }

        foreach ($categories as $category) {
            $slug = (string) ($category['slug'] ?? '');
            if ($slug === '' || $this->db->table('categories')->where('slug', $slug)->exists()) {
                continue;
            }

            $sortOrder = (int) ($category['sort_order'] ?? 0);
            $names = is_array($category['names'] ?? null) ? $category['names'] : [];

            $categoryId = (int) $this->db->insert('categories', [
                'parent_id' => null,
                'slug' => $slug,
                'image' => $category['image'] ?? null,
                'icon' => null,
                'is_featured' => $sortOrder <= 4 ? 1 : 0,
                'sort_order' => $sortOrder,
                'status' => 'active',
                'seo' => json_encode(['title' => $names['en'] ?? $slug], JSON_UNESCAPED_UNICODE),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            foreach ($names as $locale => $name) {
                $this->db->insert('category_translations', [
                    'category_id' => $categoryId,
                    'locale' => (string) $locale,
                    'name' => (string) $name,
                    'description' => null,
                ]);
            }
        }
    }
}
