<?php

declare(strict_types=1);

namespace App\Services;

use Core\Database\DatabaseManager;

/**
 * Single source for the public category tree and descendant-aware filtering.
 *
 * The catalog currently has three levels, while the storage format remains
 * flexible enough to represent a branch that naturally stops at level one or
 * two. Every consumer receives the same parent/child ordering and locale
 * fallback instead of rebuilding a slightly different tree per page.
 */
class CategoryService
{
    /** @var array<int, array{id:int,parent_id:int|null,slug:string}>|null */
    private ?array $structure = null;

    public function __construct(private readonly DatabaseManager $db)
    {
    }

    /**
     * @return array<int, array<string, mixed>> nested root categories
     */
    public function tree(string $locale, ?string $fallback = null): array
    {
        $fallback ??= (string) config('localization.fallback', 'en');
        $rows = $this->db->connection()->select(
            "SELECT c.id, c.parent_id, c.slug, c.image, c.sort_order,
                    COALESCE(ct.name, ctf.name, c.slug) AS name,
                    COALESCE(ct.description, ctf.description) AS description
             FROM categories c
             LEFT JOIN category_translations ct ON ct.category_id = c.id AND ct.locale = ?
             LEFT JOIN category_translations ctf ON ctf.category_id = c.id AND ctf.locale = ?
             WHERE c.deleted_at IS NULL AND c.status = 'active'
             ORDER BY COALESCE(c.parent_id, 0), c.sort_order ASC, c.id ASC",
            [$locale, $fallback],
        );

        $children = [];
        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $parentId = $row['parent_id'] === null ? 0 : (int) $row['parent_id'];
            $children[$parentId][] = [
                'id' => $id,
                'parent_id' => $parentId === 0 ? null : $parentId,
                'slug' => (string) $row['slug'],
                'image' => (string) ($row['image'] ?? ''),
                'name' => (string) ($row['name'] ?? $row['slug']),
                'description' => $row['description'] !== null ? (string) $row['description'] : null,
                'sort_order' => (int) $row['sort_order'],
                'children' => [],
            ];
        }

        $build = function (int $parentId, int $depth) use (&$build, $children): array {
            /* A corrupt cycle should not be able to take down the storefront. */
            if ($depth > 20) {
                return [];
            }

            $nodes = [];
            foreach ($children[$parentId] ?? [] as $node) {
                $node['depth'] = $depth;
                $node['children'] = $build((int) $node['id'], $depth + 1);
                $nodes[] = $node;
            }

            return $nodes;
        };

        return $build(0, 1);
    }

    /**
     * Flattened in display order, with a human-readable path for admin forms.
     *
     * @return array<int, array<string, mixed>>
     */
    public function flatten(string $locale, ?string $fallback = null): array
    {
        $out = [];
        $walk = function (array $nodes, array $parents) use (&$walk, &$out): void {
            foreach ($nodes as $node) {
                $names = [...$parents, (string) $node['name']];
                $children = $node['children'];
                $node['path'] = implode(' → ', $names);
                $node['children'] = [];
                $out[] = $node;
                $walk($children, $names);
            }
        };
        $walk($this->tree($locale, $fallback), []);

        return $out;
    }

    /** @return array<int, int> selected id followed by every active descendant */
    public function descendantIdsBySlug(string $slug): array
    {
        $slug = trim($slug);
        if ($slug === '') {
            return [];
        }

        foreach ($this->structure() as $id => $row) {
            if ($row['slug'] === $slug) {
                return $this->descendantIds($id);
            }
        }

        return [];
    }

    /** @return array<int, int> selected id followed by every active descendant */
    public function descendantIds(int $categoryId): array
    {
        $structure = $this->structure();
        if (!isset($structure[$categoryId])) {
            return [];
        }

        $children = [];
        foreach ($structure as $id => $row) {
            if ($row['parent_id'] !== null) {
                $children[$row['parent_id']][] = $id;
            }
        }

        $ids = [];
        $queue = [$categoryId];
        while ($queue !== [] && count($ids) <= count($structure)) {
            $id = array_shift($queue);
            if (isset($ids[$id])) {
                continue;
            }
            $ids[$id] = true;
            foreach ($children[$id] ?? [] as $childId) {
                $queue[] = $childId;
            }
        }

        return array_map('intval', array_keys($ids));
    }

    /**
     * Product totals include descendants, so a root and its branches never
     * appear empty merely because products are assigned to a leaf category.
     *
     * @return array<string, int> slug => count, plus '' => total active products
     */
    public function productCounts(): array
    {
        $connection = $this->db->connection();
        $counts = array_fill_keys(array_column($this->structure(), 'slug'), 0);
        $direct = $connection->select(
            "SELECT category_id, COUNT(*) AS n FROM products
             WHERE deleted_at IS NULL AND status = 'active' AND category_id IS NOT NULL
             GROUP BY category_id",
        );

        foreach ($direct as $row) {
            $id = (int) $row['category_id'];
            $n = (int) $row['n'];
            $visited = [];
            while (isset($this->structure()[$id]) && !isset($visited[$id])) {
                $visited[$id] = true;
                $slug = $this->structure()[$id]['slug'];
                $counts[$slug] = ($counts[$slug] ?? 0) + $n;
                $id = $this->structure()[$id]['parent_id'] ?? 0;
            }
        }

        $counts[''] = (int) ($connection->selectOne(
            "SELECT COUNT(*) AS n FROM products WHERE deleted_at IS NULL AND status = 'active'",
        )['n'] ?? 0);

        return $counts;
    }

    /** @return array<int, array{id:int,parent_id:int|null,slug:string}> */
    private function structure(): array
    {
        if ($this->structure !== null) {
            return $this->structure;
        }

        $this->structure = [];
        $rows = $this->db->connection()->select(
            "SELECT id, parent_id, slug FROM categories
             WHERE deleted_at IS NULL AND status = 'active' ORDER BY id ASC",
        );
        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $this->structure[$id] = [
                'id' => $id,
                'parent_id' => $row['parent_id'] === null ? null : (int) $row['parent_id'],
                'slug' => (string) $row['slug'],
            ];
        }

        return $this->structure;
    }
}
