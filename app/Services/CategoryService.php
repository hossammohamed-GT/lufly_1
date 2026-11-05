<?php

declare(strict_types=1);

namespace App\Services;

use Core\Database\DatabaseManager;
use Modules\Products\Services\ProductVisibility;

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

    /** @var array<string, int>|null memoised productCounts() for the request */
    private ?array $counts = null;

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
                $node['is_leaf'] = $node['children'] === [];
                $nodes[] = $node;
            }

            return $nodes;
        };

        return $build(0, 1);
    }

    /**
     * Storefront tree pruned to categories/subcategories/sub-subcategories
     * that actually contain at least one visible product.
     *
     * @param array<string, int>|null $counts
     * @return array<int, array<string, mixed>>
     */
    public function nonEmptyTree(string $locale, ?string $fallback = null, ?array $counts = null): array
    {
        $counts ??= $this->productCounts();

        $prune = static function (array $nodes) use (&$prune, $counts): array {
            $filtered = [];
            foreach ($nodes as $node) {
                $slug = (string) ($node['slug'] ?? '');
                if ($slug === '' || (int) ($counts[$slug] ?? 0) <= 0) {
                    continue;
                }
                $children = is_array($node['children'] ?? null) ? $prune($node['children']) : [];
                $node['children'] = $children;
                $node['is_leaf'] = $children === [];
                $filtered[] = $node;
            }

            return $filtered;
        };

        return $prune($this->tree($locale, $fallback));
    }

    /**
     * Ancestor-to-leaf category trail for a single category ID.
     *
     * @return array<int, array{id: int, slug: string, name: string}>
     */
    public function breadcrumbsForCategoryId(int $categoryId, string $locale, ?string $fallback = null): array
    {
        if ($categoryId <= 0) {
            return [];
        }

        $fallback ??= (string) config('localization.fallback', 'en');
        $byId = [];
        foreach ($this->flatten($locale, $fallback) as $node) {
            $byId[(int) $node['id']] = $node;
        }

        $trail = [];
        $visited = [];
        $currentId = $categoryId;
        while ($currentId > 0 && isset($byId[$currentId]) && !isset($visited[$currentId])) {
            $visited[$currentId] = true;
            $item = $byId[$currentId];
            array_unshift($trail, [
                'id' => (int) $item['id'],
                'slug' => (string) $item['slug'],
                'name' => (string) $item['name'],
            ]);
            $currentId = isset($item['parent_id']) && $item['parent_id'] !== null ? (int) $item['parent_id'] : 0;
        }

        return $trail;
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
                $node['is_leaf'] = $children === [];
                $node['children'] = [];
                $out[] = $node;
                $walk($children, $names);
            }
        };
        $walk($this->tree($locale, $fallback), []);

        return $out;
    }

    /**
     * Only leaf categories (categories with no active child categories) may
     * hold products directly.
     *
     * @return array<int, array<string, mixed>>
     */
    public function flattenLeaves(string $locale, ?string $fallback = null): array
    {
        return array_values(array_filter(
            $this->flatten($locale, $fallback),
            static fn (array $node): bool => !empty($node['is_leaf']),
        ));
    }

    /**
     * IDs of all active leaf categories (categories without active children).
     *
     * @return array<int, int>
     */
    public function leafIds(): array
    {
        $structure = $this->structure();
        $parents = [];
        foreach ($structure as $row) {
            if ($row['parent_id'] !== null && isset($structure[$row['parent_id']])) {
                $parents[$row['parent_id']] = true;
            }
        }

        $leaves = [];
        foreach ($structure as $id => $_row) {
            if (!isset($parents[$id])) {
                $leaves[] = (int) $id;
            }
        }

        return $leaves;
    }

    public function isLeaf(int $categoryId): bool
    {
        return in_array($categoryId, $this->leafIds(), true);
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

    /** Active category slug for an id, or '' when the id is unknown/retired. */
    public function slugForId(int $categoryId): string
    {
        if ($categoryId <= 0) {
            return '';
        }
        $row = $this->structure()[$categoryId] ?? null;

        return $row === null ? '' : (string) $row['slug'];
    }

    /**
     * Whether opening this branch can draw at least one product card.
     *
     * Counts alone are not proof: a media row can survive while its file is
     * missing from disk, in which case the storefront prunes the card and the
     * grid comes out empty while the badge still advertises a product.
     */
    public function rendersAnyProducts(string $slug): bool
    {
        $slug = trim($slug);
        if ($slug === '') {
            return false;
        }

        return (int) ($this->productCounts()[$slug] ?? 0) > 0;
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
     * Resolve the root collection for an empty public branch.
     *
     * Emptiness is based on products visitors can actually see, not on the
     * current search term. A populated branch therefore keeps its normal
     * no-search-results state, while an empty level-two or level-three branch
     * can consistently use its root category in both page and live searches.
     *
     * @return array{id:int,parent_id:int|null,slug:string}|null
     */
    public function rootFallbackBySlug(string $slug, string $status = 'active', bool $requireMedia = true): ?array
    {
        $slug = trim($slug);
        $structure = $this->structure();
        $selectedId = null;
        foreach ($structure as $id => $row) {
            if ($row['slug'] === $slug) {
                $selectedId = $id;
                break;
            }
        }

        if ($selectedId === null || $structure[$selectedId]['parent_id'] === null) {
            return null;
        }
        if ($this->hasVisibleProducts($selectedId, $status, $requireMedia)) {
            return null;
        }

        $rootId = $selectedId;
        $visited = [];
        while (
            isset($structure[$rootId])
            && !isset($visited[$rootId])
            && $structure[$rootId]['parent_id'] !== null
        ) {
            $visited[$rootId] = true;
            $rootId = $structure[$rootId]['parent_id'];
        }

        if (
            $rootId === $selectedId
            || !isset($structure[$rootId])
            || $structure[$rootId]['parent_id'] !== null
            || !$this->hasVisibleProducts($rootId, $status, $requireMedia)
        ) {
            return null;
        }

        return $structure[$rootId];
    }

    private function hasVisibleProducts(int $categoryId, string $status, bool $requireMedia): bool
    {
        $categoryIds = $this->descendantIds($categoryId);
        if ($categoryIds === []) {
            return false;
        }

        $where = [
            'p.deleted_at IS NULL',
            'p.status = ?',
            'p.category_id IN (' . implode(',', array_fill(0, count($categoryIds), '?')) . ')',
        ];
        $bindings = [$status, ...$categoryIds];
        if ($requireMedia) {
            $where[] = ProductVisibility::mediaReadySql('p');
        }

        return $this->db->connection()->selectOne(
            'SELECT p.id FROM products p WHERE ' . implode(' AND ', $where) . ' LIMIT 1',
            $bindings,
        ) !== null;
    }

    /**
     * Product totals include descendants, so a root and its branches never
     * appear empty merely because products are assigned to a leaf category.
     *
     * @return array<string, int> slug => count, plus '' => total active products
     */
    public function productCounts(): array
    {
        if ($this->counts !== null) {
            return $this->counts;
        }

        $connection = $this->db->connection();
        $counts = array_fill_keys(array_column($this->structure(), 'slug'), 0);
        $productRows = app(\Modules\Products\Services\ProductColorFamilyService::class)->collapseRows(
            $connection->select(
                "SELECT p.id, p.category_id FROM products p
                 WHERE p.deleted_at IS NULL AND p.status = 'active'
                   AND " . ProductVisibility::mediaReadySql('p'),
            ),
        );
        $productRows = $this->withDrawableCard($productRows);
        $direct = [];
        foreach ($productRows as $row) {
            $id = (int) $row['category_id'];
            $direct[$id] = ($direct[$id] ?? 0) + 1;
        }

        foreach ($direct as $id => $n) {
            $id = (int) $id;
            $n = (int) $n;
            $visited = [];
            while (isset($this->structure()[$id]) && !isset($visited[$id])) {
                $visited[$id] = true;
                $slug = $this->structure()[$id]['slug'];
                $counts[$slug] = ($counts[$slug] ?? 0) + $n;
                $id = $this->structure()[$id]['parent_id'] ?? 0;
            }
        }

        $counts[''] = count($productRows);

        return $this->counts = $counts;
    }

    /**
     * Keep only the products whose card can actually be drawn.
     *
     * mediaReadySql() proves the media rows exist, not that the files do: an
     * upload can point at a path that was never written (or was removed), and
     * product-card.php then refuses to render the card. Counting such a product
     * made a category advertise "1 product" while its grid came out empty, so
     * the count and the grid now share this one rule: a product counts only
     * when at least one of its product-photo files is present on disk.
     *
     * @param array<int, array<string, mixed>> $productRows
     * @return array<int, array<string, mixed>>
     */
    private function withDrawableCard(array $productRows): array
    {
        if ($productRows === []) {
            return [];
        }

        $ids = [];
        foreach ($productRows as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id > 0) {
                $ids[] = $id;
            }
        }
        if ($ids === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $drawable = [];
        foreach ($this->db->connection()->select(
            "SELECT pm.product_id, m.path
             FROM product_media pm
             INNER JOIN media m ON m.id = pm.media_id
             WHERE pm.variant_id IS NULL
               AND pm.type NOT IN ('drawing', 'situ')
               AND m.status NOT IN ('missing', 'hidden')
               AND pm.product_id IN ({$placeholders})
             ORDER BY pm.is_primary DESC, pm.sort_order ASC, pm.id ASC",
            $ids,
        ) as $row) {
            $productId = (int) ($row['product_id'] ?? 0);
            if ($productId <= 0 || isset($drawable[$productId])) {
                continue;
            }
            $path = trim((string) ($row['path'] ?? ''));
            /* asset_file_exists() memoises is_file() for the whole request. */
            if ($path !== '' && asset_file_exists($path)) {
                $drawable[$productId] = true;
            }
        }

        return array_values(array_filter(
            $productRows,
            static fn (array $row): bool => isset($drawable[(int) ($row['id'] ?? 0)]),
        ));
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
