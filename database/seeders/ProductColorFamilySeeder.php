<?php

declare(strict_types=1);

namespace Database\Seeders;

use Core\Database\Seeding\Seeder;
use RuntimeException;

/**
 * Persists the reviewed cross-product colour/finish families without changing
 * any product, translation, code, price, specification or media row.
 */
class ProductColorFamilySeeder extends Seeder
{
    public function run(): void
    {
        $file = __DIR__ . '/data/product-color-families.json';
        if (!is_file($file)) {
            return;
        }

        $data = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
        $families = $data['families'] ?? null;
        if (!is_array($families)) {
            throw new RuntimeException('The reviewed colour-family list is invalid.');
        }

        $parentIds = [];
        foreach ($this->db->select('SELECT parent_id FROM categories WHERE parent_id IS NOT NULL AND deleted_at IS NULL') as $catRow) {
            $parentIds[(int) $catRow['parent_id']] = true;
        }

        $products = [];
        foreach ($this->db->select('SELECT id, slug, category_id, status FROM products') as $row) {
            $products[(string) $row['slug']] = $row;
        }

        $seen = [];
        $relations = [];
        foreach ($families as $family) {
            $slugs = $family['products'] ?? null;
            if (!is_array($slugs) || count($slugs) < 2) {
                throw new RuntimeException('Every colour family needs at least two product slugs.');
            }

            $ids = [];
            $category = null;
            foreach ($slugs as $slug) {
                $slug = trim((string) $slug);
                if ($slug === '' || !isset($products[$slug])) {
                    throw new RuntimeException('Unknown product in a colour family: ' . $slug);
                }
                if (isset($seen[$slug])) {
                    throw new RuntimeException('Product appears in more than one colour family: ' . $slug);
                }
                $seen[$slug] = true;

                $product = $products[$slug];
                if ((string) $product['status'] !== 'active') {
                    throw new RuntimeException('A reviewed colour family contains a non-public product: ' . $slug);
                }
                $category ??= $product['category_id'] === null ? null : (int) $product['category_id'];
                $productCategory = $product['category_id'] === null ? null : (int) $product['category_id'];
                if ($category !== $productCategory) {
                    throw new RuntimeException('A colour family crosses product categories: ' . $slug);
                }
                if ($productCategory !== null && isset($parentIds[$productCategory])) {
                    throw new RuntimeException('A colour family product is assigned to a non-leaf category: ' . $slug);
                }
                $ids[] = (int) $product['id'];
            }

            $source = array_shift($ids);
            foreach ($ids as $id) {
                $relations[] = [$source, $id];
            }
        }

        $this->db->table('product_relations')->where('relation_type', 'color_variant')->delete();
        foreach ($relations as [$productId, $relatedId]) {
            $this->db->insert('product_relations', [
                'product_id' => $productId,
                'related_product_id' => $relatedId,
                'relation_type' => 'color_variant',
            ]);
        }
    }
}
