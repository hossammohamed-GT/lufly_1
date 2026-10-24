<?php

declare(strict_types=1);

namespace Database\Seeders;

use Core\Database\Seeding\Seeder;

/**
 * Seeds the three-level LUFLY category atlas.
 *
 * Each item references its parent by slug, so the JSON remains readable and
 * does not depend on auto-increment IDs. Parents are inserted before children
 * over as many passes as necessary; unresolved parents and depth overflow are
 * rejected rather than silently flattening the tree.
 */
class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $file = __DIR__ . '/data/categories.json';
        if (!is_file($file)) {
            return;
        }

        $categories = json_decode((string) file_get_contents($file), true);
        if (!is_array($categories) || $categories === []) {
            return;
        }

        $categoryIds = [];
        $categoryImages = [];
        $depths = [];
        foreach ($this->db->table('categories')->get() as $category) {
            $slug = (string) $category['slug'];
            $id = (int) $category['id'];
            $categoryIds[$slug] = $id;
            $categoryImages[$slug] = trim((string) ($category['image'] ?? ''));
            $depths[$slug] = $this->depth($id);
        }

        $pending = array_values($categories);
        while ($pending !== []) {
            $progress = false;

            foreach ($pending as $index => $category) {
                $slug = trim((string) ($category['slug'] ?? ''));
                if ($slug === '') {
                    throw new \RuntimeException('Every seeded category needs a slug.');
                }

                $image = trim((string) ($category['image'] ?? ''));

                if (isset($categoryIds[$slug])) {
                    /* Existing categories still need their curated visual
                       synchronized; skipping them leaves the storefront on
                       the old shared fallback after an image-only update. */
                    if (($categoryImages[$slug] ?? '') !== $image) {
                        $this->db->table('categories')->where('id', $categoryIds[$slug])->update([
                            'image' => $image !== '' ? $image : null,
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                        $categoryImages[$slug] = $image;
                    }

                    unset($pending[$index]);
                    $progress = true;
                    continue;
                }

                $parentSlug = trim((string) ($category['parent'] ?? ''));
                if ($parentSlug !== '' && !isset($categoryIds[$parentSlug])) {
                    continue;
                }

                $depth = $parentSlug === '' ? 1 : $depths[$parentSlug] + 1;
                if ($depth > 3) {
                    throw new \RuntimeException("Category [{$slug}] exceeds the three-level catalog limit.");
                }

                $names = is_array($category['names'] ?? null) ? $category['names'] : [];
                $now = date('Y-m-d H:i:s');
                $categoryId = (int) $this->db->insert('categories', [
                    'parent_id' => $parentSlug === '' ? null : $categoryIds[$parentSlug],
                    'slug' => $slug,
                    'image' => $image !== '' ? $image : null,
                    'icon' => null,
                    'is_featured' => $depth === 1 ? 1 : 0,
                    'sort_order' => (int) ($category['sort_order'] ?? 0),
                    'status' => 'active',
                    'seo' => json_encode(['title' => $names['en'] ?? $slug], JSON_UNESCAPED_UNICODE),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                foreach ($names as $locale => $name) {
                    $name = trim((string) $name);
                    if ($name === '') {
                        continue;
                    }
                    $this->db->insert('category_translations', [
                        'category_id' => $categoryId,
                        'locale' => (string) $locale,
                        'name' => $name,
                        'description' => null,
                    ]);
                }

                $categoryIds[$slug] = $categoryId;
                $depths[$slug] = $depth;
                unset($pending[$index]);
                $progress = true;
            }

            if (!$progress) {
                throw new \RuntimeException('Unresolved parent or circular reference in categories.json.');
            }

            $pending = array_values($pending);
        }
    }

    private function depth(int $categoryId): int
    {
        $depth = 1;
        $visited = [];
        while (true) {
            if (isset($visited[$categoryId])) {
                throw new \RuntimeException('Circular category parent reference in the database.');
            }
            $visited[$categoryId] = true;
            $row = $this->db->table('categories')->where('id', $categoryId)->first();
            if ($row === null || $row['parent_id'] === null) {
                return $depth;
            }
            $depth++;
            if ($depth > 3) {
                throw new \RuntimeException('Existing category tree exceeds the three-level catalog limit.');
            }
            $categoryId = (int) $row['parent_id'];
        }
    }
}
