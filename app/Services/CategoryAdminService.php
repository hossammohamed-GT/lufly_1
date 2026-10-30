<?php

declare(strict_types=1);

namespace App\Services;

use Core\Database\DatabaseManager;
use Core\Exceptions\NotFoundException;
use Core\Support\Str;
use InvalidArgumentException;

/**
 * Write-side category-tree management for the admin panel.
 *
 * The storefront reads through CategoryService; everything that mutates the
 * tree lives here so the catalog invariants are enforced in exactly one
 * place:
 *  - at most three levels (the seeded atlas depth);
 *  - dotted path slugs (`bathroom.sanitary-ware.toilets`) stay consistent —
 *    renaming a segment or moving a node rewrites every descendant slug,
 *    the media-library collection labels and the product search keywords of
 *    the whole subtree inside one transaction;
 *  - a category that directly holds products must stay a leaf, so it can
 *    never gain children until its products are moved away;
 *  - a category with children or products cannot be deleted.
 */
final class CategoryAdminService
{
    public const MAX_DEPTH = 3;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LocalizationService $localization,
        private readonly ActivityLogger $activity,
    ) {
    }

    /* ------------------------------------------------------------------ read */

    /** @return array<string, mixed> raw categories row */
    public function find(int $id): array
    {
        $row = $this->db->connection()->selectOne(
            'SELECT * FROM categories WHERE id = ? AND deleted_at IS NULL',
            [$id],
        );
        if ($row === null) {
            throw new NotFoundException('Category not found.');
        }

        return $row;
    }

    /** @return array<string, array<string, mixed>> locale => translation row */
    public function translations(int $id): array
    {
        $out = [];
        foreach ($this->db->connection()->select(
            'SELECT * FROM category_translations WHERE category_id = ?',
            [$id],
        ) as $row) {
            $out[(string) $row['locale']] = $row;
        }

        return $out;
    }

    /**
     * Flattened admin tree (every status), display-ordered, with depth,
     * path label and product counts per node.
     *
     * @return array<int, array<string, mixed>>
     */
    public function flatTree(string $locale = 'en', string $fallback = 'en'): array
    {
        $connection = $this->db->connection();
        $rows = $connection->select(
            "SELECT c.id, c.parent_id, c.slug, c.image, c.icon, c.is_featured, c.sort_order, c.status,
                    COALESCE(ct.name, ctf.name, c.slug) AS name
             FROM categories c
             LEFT JOIN category_translations ct ON ct.category_id = c.id AND ct.locale = ?
             LEFT JOIN category_translations ctf ON ctf.category_id = c.id AND ctf.locale = ?
             WHERE c.deleted_at IS NULL
             ORDER BY COALESCE(c.parent_id, 0), c.sort_order ASC, c.id ASC",
            [$locale, $fallback],
        );

        $direct = [];
        foreach ($connection->select(
            'SELECT category_id, COUNT(*) AS total FROM products
             WHERE deleted_at IS NULL AND category_id IS NOT NULL GROUP BY category_id',
        ) as $row) {
            $direct[(int) $row['category_id']] = (int) $row['total'];
        }

        $children = [];
        $byId = [];
        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $parentId = $row['parent_id'] === null ? 0 : (int) $row['parent_id'];
            $node = [
                'id' => $id,
                'parent_id' => $parentId === 0 ? null : $parentId,
                'slug' => (string) $row['slug'],
                'image' => (string) ($row['image'] ?? ''),
                'icon' => (string) ($row['icon'] ?? ''),
                'name' => (string) ($row['name'] ?? $row['slug']),
                'is_featured' => (int) $row['is_featured'] === 1,
                'sort_order' => (int) $row['sort_order'],
                'status' => (string) $row['status'],
                'direct_products' => $direct[$id] ?? 0,
                'subtree_products' => $direct[$id] ?? 0,
                'children_count' => 0,
                'depth' => 0,
                'path' => '',
            ];
            $byId[$id] = $node;
            $children[$parentId][] = $id;
        }

        /* Roll subtree product totals up towards the roots. */
        $rollup = function (int $id) use (&$rollup, &$byId, $children): int {
            $total = $byId[$id]['direct_products'];
            foreach ($children[$id] ?? [] as $childId) {
                $total += $rollup($childId);
            }
            $byId[$id]['subtree_products'] = $total;
            $byId[$id]['children_count'] = count($children[$id] ?? []);

            return $total;
        };
        foreach ($children[0] ?? [] as $rootId) {
            $rollup($rootId);
        }

        $out = [];
        $walk = function (int $parentId, array $parents, int $depth) use (&$walk, &$out, $byId, $children): void {
            if ($depth > 20) {
                return;
            }
            foreach ($children[$parentId] ?? [] as $id) {
                $node = $byId[$id];
                $names = [...$parents, $node['name']];
                $node['depth'] = $depth;
                $node['path'] = implode(' → ', $names);
                $out[] = $node;
                $walk($id, $names, $depth + 1);
            }
        };
        $walk(0, [], 1);

        return $out;
    }

    /* ----------------------------------------------------------------- write */

    /**
     * @param array<string, mixed> $data parent_id, slug_segment, image, icon,
     *                                    is_featured, sort_order, status, seo_title
     * @param array<string, array<string, mixed>> $translations locale => name/description
     */
    public function create(array $data, array $translations): int
    {
        $connection = $this->db->connection();
        $structure = $this->structure();

        $parentId = $this->nullableParentId($data['parent_id'] ?? null);
        if ($parentId !== null && !isset($structure[$parentId])) {
            throw new InvalidArgumentException(trans('categories.invalid_parent'));
        }
        if ($parentId !== null) {
            if ($this->depthOf($parentId, $structure) >= self::MAX_DEPTH) {
                throw new InvalidArgumentException(trans('categories.depth_exceeded'));
            }
            if ($this->directProductCount($parentId) > 0) {
                throw new InvalidArgumentException(trans('categories.parent_has_products'));
            }
        }

        $segment = $this->segmentFrom($data, $translations);
        $parentSlug = $parentId !== null ? $structure[$parentId]['slug'] : '';
        $slug = $this->uniqueSlug($parentSlug !== '' ? $parentSlug . '.' . $segment : $segment);

        $now = date('Y-m-d H:i:s');
        $id = (int) $connection->transaction(function () use ($connection, $data, $translations, $parentId, $slug, $now): int {
            $id = (int) $connection->table('categories')->insert([
                'parent_id' => $parentId,
                'slug' => $slug,
                'image' => $this->nullableString($data['image'] ?? null),
                'icon' => $this->nullableString($data['icon'] ?? null),
                'is_featured' => !empty($data['is_featured']) ? 1 : 0,
                'sort_order' => isset($data['sort_order']) ? (int) $data['sort_order'] : 0,
                'status' => $this->statusFrom($data),
                'seo' => $this->seoJson($data, $translations),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $this->localization->syncTranslations('category', $id, $translations);

            return $id;
        });

        $this->activity->created('category', $id, ['slug' => $slug]);

        return $id;
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, array<string, mixed>> $translations
     */
    public function update(int $id, array $data, array $translations): void
    {
        $connection = $this->db->connection();
        $current = $this->find($id);
        $structure = $this->structure();
        if (!isset($structure[$id])) {
            throw new InvalidArgumentException(trans('categories.invalid_parent'));
        }

        $oldSlug = (string) $current['slug'];
        $oldParentId = $current['parent_id'] === null ? null : (int) $current['parent_id'];
        $newParentId = $this->nullableParentId($data['parent_id'] ?? null);
        $descendants = $this->descendantIds($id, $structure);

        if ($newParentId !== null) {
            if (!isset($structure[$newParentId]) || $newParentId === $id || in_array($newParentId, $descendants, true)) {
                throw new InvalidArgumentException(trans('categories.invalid_parent'));
            }
            if ($newParentId !== $oldParentId && $this->directProductCount($newParentId) > 0) {
                throw new InvalidArgumentException(trans('categories.parent_has_products'));
            }
            $subtreeHeight = 1 + $this->maxDepthBelow($id, $structure);
            if ($this->depthOf($newParentId, $structure) + $subtreeHeight > self::MAX_DEPTH) {
                throw new InvalidArgumentException(trans('categories.depth_exceeded'));
            }
        }

        $segment = $this->segmentFrom($data, $translations, $oldSlug);
        $parentSlug = $newParentId !== null ? $structure[$newParentId]['slug'] : '';
        $newSlug = $parentSlug !== '' ? $parentSlug . '.' . $segment : $segment;
        if ($newSlug !== $oldSlug) {
            $ignore = [$id, ...$descendants];
            $newSlug = $this->uniqueSlug($newSlug, $ignore);
            /* A "-2" suffix on the parent changes the cascade prefix only;
               descendant suffixes are preserved verbatim from the old slug. */
            foreach ($descendants as $descendantId) {
                $suffix = substr($structure[$descendantId]['slug'], strlen($oldSlug));
                $candidate = $newSlug . $suffix;
                if ($this->slugExists($candidate, $ignore)) {
                    throw new InvalidArgumentException(trans('categories.slug_taken'));
                }
            }
        }

        $now = date('Y-m-d H:i:s');
        $connection->transaction(function () use (
            $connection, $id, $data, $translations, $newParentId, $oldSlug, $newSlug, $descendants, $structure, $now
        ): void {
            $connection->table('categories')->where('id', $id)->update([
                'parent_id' => $newParentId,
                'slug' => $newSlug,
                'image' => array_key_exists('image', $data) ? $this->nullableString($data['image']) : $this->findRaw($id)['image'],
                'icon' => $this->nullableString($data['icon'] ?? null),
                'is_featured' => !empty($data['is_featured']) ? 1 : 0,
                'sort_order' => isset($data['sort_order']) ? (int) $data['sort_order'] : 0,
                'status' => $this->statusFrom($data),
                'seo' => $this->seoJson($data, $translations),
                'updated_at' => $now,
            ]);

            if ($newSlug !== $oldSlug) {
                $this->afterSlugChange($id, $newSlug, $now);
                foreach ($descendants as $descendantId) {
                    $descendantSlug = $newSlug . substr($structure[$descendantId]['slug'], strlen($oldSlug));
                    $connection->table('categories')->where('id', $descendantId)->update([
                        'slug' => $descendantSlug,
                        'updated_at' => $now,
                    ]);
                    $this->afterSlugChange($descendantId, $descendantSlug, $now);
                }
            }

            $this->localization->syncTranslations('category', $id, $translations);
        });

        $this->activity->updated('category', $id);
    }

    /**
     * One-field inline edit from a category row. Rebuilds the full update
     * payload from the stored category so every cascade (slug-path rewrite,
     * keyword rebuild, depth/leaf rules) stays in one code path.
     *
     * @return array{id: int, field: string, value: mixed, slug: string}
     */
    public function inlineUpdate(int $id, string $field, mixed $value, string $locale): array
    {
        $row = $this->find($id);

        $translations = [];
        foreach ($this->translations($id) as $loc => $translation) {
            $translations[$loc] = [
                'name' => (string) ($translation['name'] ?? ''),
                'description' => (string) ($translation['description'] ?? ''),
            ];
        }
        $seo = json_decode((string) ($row['seo'] ?? ''), true);

        $data = [
            'parent_id' => $row['parent_id'] === null ? '' : (int) $row['parent_id'],
            'slug_segment' => '',
            'icon' => (string) ($row['icon'] ?? ''),
            'is_featured' => !empty($row['is_featured']) ? 1 : '',
            'sort_order' => (int) ($row['sort_order'] ?? 0),
            'status' => (string) ($row['status'] ?? 'active'),
            'seo_title' => (string) (is_array($seo) ? ($seo['title'] ?? '') : ''),
        ];

        $resultValue = null;
        switch ($field) {
            case 'name':
                $name = trim((string) $value);
                if ($name === '') {
                    throw new InvalidArgumentException(trans('categories.slug_required'));
                }
                $translations[$locale]['name'] = $name;
                $resultValue = $name;
                break;
            case 'status':
                $status = trim((string) $value);
                if (!in_array($status, ['active', 'inactive'], true)) {
                    throw new InvalidArgumentException(trans('categories.invalid_parent'));
                }
                $data['status'] = $status;
                $resultValue = $status;
                break;
            case 'is_featured':
                $featured = filter_var($value, FILTER_VALIDATE_BOOL) ? 1 : '';
                $data['is_featured'] = $featured;
                $resultValue = $featured === 1 ? 1 : 0;
                break;
            case 'sort_order':
                $data['sort_order'] = max(0, (int) $value);
                $resultValue = $data['sort_order'];
                break;
            case 'parent_id':
                $data['parent_id'] = (int) $value > 0 ? (int) $value : '';
                $resultValue = (int) $value;
                break;
            default:
                throw new InvalidArgumentException(trans('admin.common.invalid_field'));
        }

        $this->update($id, $data, $translations);

        $fresh = $this->find($id);

        return [
            'id' => $id,
            'field' => $field,
            'value' => $resultValue,
            'slug' => (string) $fresh['slug'],
        ];
    }

    /**
     * Create a category from just a name (+ optional parent) for the inline
     * "add" affordances. The new node is active and sits after its siblings.
     */
    public function quickCreate(string $name, int $parentId, string $locale): int
    {
        $name = trim($name);
        if ($name === '') {
            throw new InvalidArgumentException(trans('categories.slug_required'));
        }

        $connection = $this->db->connection();
        $sibling = $connection->selectOne(
            'SELECT MAX(sort_order) AS max_sort FROM categories
             WHERE parent_id ' . ($parentId > 0 ? '= ?' : 'IS NULL') . ' AND deleted_at IS NULL',
            $parentId > 0 ? [$parentId] : [],
        );

        $fallback = (string) config('localization.fallback', 'en');

        return $this->create([
            'parent_id' => $parentId > 0 ? $parentId : '',
            'status' => 'active',
            'is_featured' => '',
            'sort_order' => (int) ($sibling['max_sort'] ?? 0) + 1,
        ], [
            $locale => ['name' => $name],
            $fallback => ['name' => $name],
        ]);
    }

    /** Point the category at an image path, or clear the image when null. */
    public function setImage(int $id, ?string $path): void
    {
        $this->db->connection()->table('categories')->where('id', $id)->update([
            'image' => $path,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function delete(int $id): void
    {
        $connection = $this->db->connection();
        $current = $this->find($id);

        $children = $connection->selectOne(
            'SELECT COUNT(*) AS total FROM categories WHERE parent_id = ? AND deleted_at IS NULL',
            [$id],
        );
        if ((int) ($children['total'] ?? 0) > 0) {
            throw new InvalidArgumentException(trans('categories.delete_blocked_children'));
        }
        if ($this->directProductCount($id) > 0) {
            throw new InvalidArgumentException(trans('categories.delete_blocked_products'));
        }

        $now = date('Y-m-d H:i:s');
        /* `slug` carries a UNIQUE constraint spanning soft-deleted rows:
           retire the slug (suffixed with the id) so the same dotted path can
           be reused later instead of silently coming back as "-2". */
        $connection->table('categories')->where('id', $id)->update([
            'slug' => mb_substr((string) $current['slug'] . '#del#' . $id, 0, 255),
            'deleted_at' => $now,
            'updated_at' => $now,
        ]);

        $this->activity->deleted('category', $id);
    }

    /** Swap with the previous/next sibling, then renumber the sibling group. */
    public function move(int $id, string $direction): void
    {
        $connection = $this->db->connection();
        $current = $this->find($id);
        $parentId = $current['parent_id'] === null ? null : (int) $current['parent_id'];

        $siblings = $connection->select(
            'SELECT id FROM categories WHERE parent_id ' . ($parentId === null ? 'IS NULL' : '= ?') . '
               AND deleted_at IS NULL ORDER BY sort_order ASC, id ASC',
            $parentId === null ? [] : [$parentId],
        );
        $ids = array_map(static fn (array $row): int => (int) $row['id'], $siblings);
        $index = array_search($id, $ids, true);
        if ($index === false) {
            return;
        }
        $swap = $direction === 'up' ? $index - 1 : $index + 1;
        if ($swap < 0 || $swap >= count($ids)) {
            return;
        }
        [$ids[$index], $ids[$swap]] = [$ids[$swap], $ids[$index]];

        $now = date('Y-m-d H:i:s');
        $connection->transaction(function () use ($connection, $ids, $now): void {
            foreach ($ids as $position => $siblingId) {
                $connection->table('categories')->where('id', $siblingId)->update([
                    'sort_order' => $position + 1,
                    'updated_at' => $now,
                ]);
            }
        });

        $this->activity->updated('category', $id);
    }

    public function toggleStatus(int $id): string
    {
        $current = $this->find($id);
        $status = (string) $current['status'] === 'active' ? 'inactive' : 'active';
        $this->db->connection()->table('categories')->where('id', $id)->update([
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->activity->updated('category', $id);

        return $status;
    }

    /* ---------------------------------------------------------------- helpers */

    /**
     * Keep the artifacts keyed by a category slug in step after a rename or
     * move: media-library collection labels and product search keywords.
     */
    private function afterSlugChange(int $categoryId, string $newSlug, string $now): void
    {
        $connection = $this->db->connection();
        $connection->affect(
            'UPDATE media SET collection = ?, updated_at = ?
             WHERE id IN (
                SELECT media_id FROM product_media
                WHERE product_id IN (SELECT id FROM products WHERE category_id = ? AND deleted_at IS NULL)
             )',
            [$newSlug, $now, $categoryId],
        );

        $productIds = array_map(
            static fn (array $row): int => (int) $row['id'],
            $connection->select(
                'SELECT id FROM products WHERE category_id = ? AND deleted_at IS NULL',
                [$categoryId],
            ),
        );
        if ($productIds !== []) {
            $this->rebuildKeywords($productIds, $categoryId, $newSlug);
        }
    }

    /** @param int[] $productIds */
    private function rebuildKeywords(array $productIds, int $categoryId, string $categorySlug): void
    {
        $connection = $this->db->connection();
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));

        $names = [];
        foreach ($connection->select(
            "SELECT product_id, name FROM product_translations WHERE locale = 'en' AND product_id IN ({$placeholders})",
            $productIds,
        ) as $row) {
            $names[(int) $row['product_id']] = (string) $row['name'];
        }

        $labels = [];
        foreach ($connection->select(
            'SELECT name FROM category_translations WHERE category_id = ?',
            [$categoryId],
        ) as $row) {
            $label = trim((string) ($row['name'] ?? ''));
            if ($label !== '') {
                $labels[] = mb_strtolower($label);
            }
        }

        $seatCodes = [];
        foreach ($connection->select(
            "SELECT pr.product_id, p.model_code FROM product_relations pr
             INNER JOIN products p ON p.id = pr.related_product_id AND p.deleted_at IS NULL
             WHERE pr.relation_type = 'seat_cover' AND pr.product_id IN ({$placeholders})",
            $productIds,
        ) as $row) {
            $code = trim((string) ($row['model_code'] ?? ''));
            if ($code !== '') {
                $seatCodes[(int) $row['product_id']][] = mb_strtolower($code);
            }
        }

        foreach ($connection->select(
            "SELECT id, model_code FROM products WHERE id IN ({$placeholders})",
            $productIds,
        ) as $product) {
            $productId = (int) $product['id'];
            $keywords = ['lufly'];
            $modelCode = trim((string) ($product['model_code'] ?? ''));
            if ($modelCode !== '') {
                $keywords[] = mb_strtolower($modelCode);
            }
            foreach (preg_split('/[^\p{L}\p{N}-]+/u', $names[$productId] ?? '') ?: [] as $word) {
                if (mb_strlen($word) >= 3) {
                    $keywords[] = mb_strtolower($word);
                }
            }
            foreach (explode('.', $categorySlug) as $segment) {
                $segment = trim(str_replace('-', ' ', $segment));
                if ($segment !== '') {
                    $keywords[] = mb_strtolower($segment);
                }
            }
            $keywords = [...$keywords, ...$labels, ...($seatCodes[$productId] ?? [])];

            $connection->table('product_search_keywords')->where('product_id', $productId)->delete();
            foreach (array_unique($keywords) as $keyword) {
                $connection->table('product_search_keywords')->insert([
                    'product_id' => $productId,
                    'keyword' => mb_substr($keyword, 0, 150),
                ]);
            }
        }
    }

    /** @return array<string, mixed> */
    private function findRaw(int $id): array
    {
        return $this->db->connection()->selectOne('SELECT * FROM categories WHERE id = ?', [$id]) ?? [];
    }

    /** @return array<int, array{id: int, parent_id: int|null, slug: string}> */
    private function structure(): array
    {
        $structure = [];
        foreach ($this->db->connection()->select(
            'SELECT id, parent_id, slug FROM categories WHERE deleted_at IS NULL ORDER BY id ASC',
        ) as $row) {
            $id = (int) $row['id'];
            $structure[$id] = [
                'id' => $id,
                'parent_id' => $row['parent_id'] === null ? null : (int) $row['parent_id'],
                'slug' => (string) $row['slug'],
            ];
        }

        return $structure;
    }

    /** @param array<int, array{id: int, parent_id: int|null, slug: string}> $structure */
    private function depthOf(int $id, array $structure): int
    {
        $depth = 0;
        $visited = [];
        while (isset($structure[$id]) && !isset($visited[$id])) {
            $visited[$id] = true;
            $depth++;
            $id = $structure[$id]['parent_id'] ?? 0;
        }

        return $depth;
    }

    /** @param array<int, array{id: int, parent_id: int|null, slug: string}> $structure */
    private function maxDepthBelow(int $id, array $structure): int
    {
        $max = 0;
        foreach ($structure as $row) {
            if ($row['parent_id'] === $id) {
                $max = max($max, 1 + $this->maxDepthBelow($row['id'], $structure));
            }
        }

        return $max;
    }

    /**
     * @param array<int, array{id: int, parent_id: int|null, slug: string}> $structure
     * @return int[] descendant ids ordered parent-before-child
     */
    private function descendantIds(int $id, array $structure): array
    {
        $out = [];
        $queue = [$id];
        while ($queue !== []) {
            $currentId = array_shift($queue);
            foreach ($structure as $row) {
                if ($row['parent_id'] === $currentId && !in_array($row['id'], $out, true)) {
                    $out[] = $row['id'];
                    $queue[] = $row['id'];
                }
            }
            if (count($out) > count($structure)) {
                break;
            }
        }

        return $out;
    }

    private function directProductCount(int $categoryId): int
    {
        $row = $this->db->connection()->selectOne(
            'SELECT COUNT(*) AS total FROM products WHERE category_id = ? AND deleted_at IS NULL',
            [$categoryId],
        );

        return (int) ($row['total'] ?? 0);
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, array<string, mixed>> $translations
     */
    private function segmentFrom(array $data, array $translations, ?string $currentSlug = null): string
    {
        $segment = Str::slug((string) ($data['slug_segment'] ?? ''));
        if ($segment === '' && $currentSlug !== null) {
            $parts = explode('.', $currentSlug);
            $segment = (string) end($parts);
        }
        if ($segment === '') {
            $segment = Str::slug((string) ($translations['en']['name'] ?? ''));
        }
        if ($segment === '') {
            throw new InvalidArgumentException(trans('categories.slug_required'));
        }

        return mb_substr($segment, 0, 80);
    }

    /** @param int[] $ignoreIds */
    private function slugExists(string $slug, array $ignoreIds = []): bool
    {
        $sql = 'SELECT id FROM categories WHERE slug = ?';
        $bindings = [$slug];
        if ($ignoreIds !== []) {
            $sql .= ' AND id NOT IN (' . implode(',', array_fill(0, count($ignoreIds), '?')) . ')';
            $bindings = [...$bindings, ...$ignoreIds];
        }

        return $this->db->connection()->selectOne($sql . ' LIMIT 1', $bindings) !== null;
    }

    /** @param int[] $ignoreIds */
    private function uniqueSlug(string $slug, array $ignoreIds = []): string
    {
        $candidate = $slug;
        $suffix = 1;
        while ($this->slugExists($candidate, $ignoreIds)) {
            $suffix++;
            $candidate = $slug . '-' . $suffix;
        }

        return $candidate;
    }

    private function nullableParentId(mixed $value): ?int
    {
        $id = (int) ($value ?? 0);

        return $id > 0 ? $id : null;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value !== '' ? $value : null;
    }

    /** @param array<string, mixed> $data */
    private function statusFrom(array $data): string
    {
        return in_array((string) ($data['status'] ?? 'active'), ['active', 'inactive'], true)
            ? (string) $data['status']
            : 'active';
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, array<string, mixed>> $translations
     */
    private function seoJson(array $data, array $translations): string
    {
        $title = trim((string) ($data['seo_title'] ?? ''));
        if ($title === '') {
            $title = trim((string) ($translations['en']['name'] ?? ''));
        }

        return (string) json_encode(['title' => $title], JSON_UNESCAPED_UNICODE);
    }
}
