<?php

declare(strict_types=1);

namespace Modules\Products\Repositories;

use App\Repositories\Repository;
use App\Services\CategoryService;
use Core\Database\Paginator;
use Modules\Products\Models\Product;
use Modules\Products\Services\ProductColorFamilyService;
use Modules\Products\Services\ProductVisibility;

class ProductRepository extends Repository
{
    protected string $model = Product::class;

    protected bool $auditing = true;

    protected string $auditEntity = 'product';

    public function findBySlug(string $slug): ?Product
    {
        /** @var Product|null $product */
        $product = Product::query()->where('slug', $slug)->first();

        return $product;
    }

    /**
     * Storefront search + filter + sort.
     *
     * Bindings follow placeholder order: joins first, then WHERE. Category
     * slugs are expanded to the selected id and all active descendant ids.
     *
     * @param array{
     *     status?: string,
     *     search?: string,
     *     locale?: string,
     *     category_slug?: string,
     *     category_id?: int|string,
     *     featured?: bool,
     *     sort?: string,
     *     require_media?: bool,
     * } $filters
     */
    public function search(array $filters, int $page = 1, int $perPage = 12): Paginator
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $locale = $filters['locale'] ?? 'en';
        $connection = Product::query()->connection();
        $status = (string) ($filters['status'] ?? '');
        $publicStatus = in_array($status, ProductVisibility::PUBLIC_STATUSES, true);
        $requireMedia = (bool) ($filters['require_media'] ?? $publicStatus);
        $colorFamilies = $publicStatus
            ? app(ProductColorFamilyService::class)
            : null;

        $joins = [
            'name' => ' INNER JOIN product_translations pt ON pt.product_id = p.id AND pt.locale = ?',
        ];
        $usedJoins = [];
        $joinBindings = [];
        $where = ['p.deleted_at IS NULL'];
        $whereBindings = [];

        if (($filters['status'] ?? '') !== '') {
            $where[] = 'p.status = ?';
            $whereBindings[] = $filters['status'];
        }
        if ($requireMedia) {
            $where[] = ProductVisibility::mediaReadySql('p');
        }

        $hasCategoryFilter = !empty($filters['category_id']) || !empty($filters['category_slug']);
        $categoryService = app(CategoryService::class);
        $categorySlug = (string) ($filters['category_slug'] ?? '');
        $categoryIds = !empty($filters['category_id'])
            ? $categoryService->descendantIds((int) $filters['category_id'])
            : $categoryService->descendantIdsBySlug($categorySlug);
        if ($hasCategoryFilter) {
            if ($categoryIds === []) {
                $where[] = '1 = 0';
            } else {
                $where[] = 'p.category_id IN (' . implode(',', array_fill(0, count($categoryIds), '?')) . ')';
                $whereBindings = [...$whereBindings, ...$categoryIds];
            }
        }

        if (($filters['search'] ?? '') !== '') {
            if (!in_array('name', $usedJoins, true)) {
                $usedJoins[] = 'name';
                $joinBindings[] = $locale;
            }
            $where[] = '(pt.name LIKE ? OR p.model_code LIKE ?)';
            $whereBindings[] = '%' . $filters['search'] . '%';
            $whereBindings[] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['featured'])) {
            $where[] = 'p.is_featured = 1';
        }

        $sort = (string) ($filters['sort'] ?? 'newest');
        if ($sort === 'name' && !in_array('name', $usedJoins, true)) {
            $usedJoins[] = 'name';
            $joinBindings[] = $locale;
        }

        $searchJoin = '';
        foreach ($usedJoins as $key) {
            $searchJoin .= $joins[$key];
        }

        $whereSql = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);

        $nameCollate = match ($connection->driver()) {
            'sqlite' => 'pt.name COLLATE NOCASE ASC',
            'mysql' => 'pt.name ASC',
            default => 'LOWER(pt.name) ASC',
        };

        $orderBySql = match ($sort) {
            'model' => ' ORDER BY p.model_code ASC, p.id DESC',
            'name' => " ORDER BY {$nameCollate}, p.id DESC",
            default => ' ORDER BY p.id DESC',
        };

        if ($sort === 'curated') {
            /* Discovery order is deterministic, but not an import-order dump.
               Fetch lightweight rows, then rotate categories and model families
               before hydrating only the products visible on this page. */
            $discoveryRows = $connection->select(
                "SELECT DISTINCT p.id, p.category_id, p.model_code, p.is_featured,
                        CASE WHEN EXISTS (
                            SELECT 1 FROM product_media pm
                            INNER JOIN media m ON m.id = pm.media_id
                            WHERE pm.product_id = p.id
                              AND pm.variant_id IS NULL
                              AND pm.type NOT IN ('drawing', 'situ')
                              AND m.status = 'active'
                        ) THEN 1 ELSE 0 END AS has_photo
                 FROM products p {$searchJoin} {$whereSql}
                 ORDER BY p.is_featured DESC, has_photo DESC, p.id DESC",
                array_merge($joinBindings, $whereBindings),
            );

            if ($colorFamilies !== null) {
                $discoveryRows = $colorFamilies->collapseRows($discoveryRows);
            }
            $orderedIds = $this->diversifyDiscovery($discoveryRows);
            $pageIds = array_slice($orderedIds, ($page - 1) * $perPage, $perPage);
            $items = $this->hydrateOrderedIds($pageIds);

            return new Paginator($items, count($orderedIds), $page, $perPage);
        }

        /* product_translations is 1-to-1 per (product_id, locale), so omitting
           DISTINCT avoids MySQL ONLY_FULL_GROUP_BY rejection on ORDER BY pt.name. */
        $sql = "SELECT p.* FROM products p {$searchJoin} {$whereSql}{$orderBySql}";

        if ($colorFamilies !== null) {
            /* Collapse before counting and slicing. A search for one specific
               colour still opens that colour first and exposes its reviewed
               siblings through the same-card selector. */
            $rows = $colorFamilies->collapseRows($connection->select(
                $sql,
                array_merge($joinBindings, $whereBindings),
            ));
            $total = count($rows);
            $rows = array_slice($rows, ($page - 1) * $perPage, $perPage);
        } else {
            /* Administration keeps every independent product row visible. */
            $total = (int) ($connection->selectOne(
                "SELECT COUNT(DISTINCT p.id) AS aggregate FROM products p {$searchJoin} {$whereSql}",
                array_merge($joinBindings, $whereBindings),
            )['aggregate'] ?? 0);

            $rows = $connection->select(
                $sql . ' LIMIT ' . max(1, $perPage) . ' OFFSET ' . (($page - 1) * $perPage),
                array_merge($joinBindings, $whereBindings),
            );
        }

        $items = array_map(static fn (array $row): Product => Product::fromRow($row), $rows);
        Product::eagerLoad($items);

        return new Paginator($items, $total, $page, $perPage);
    }

    /**
     * Round-robin categories, then model families inside each category.
     *
     * The source rows are already ordered by featured status, photographic
     * media and recency. Each bucket keeps that order, while the first page
     * cannot become a long run of adjacent colour/model variants. No random
     * ordering is used, so pagination remains stable and every id appears once.
     *
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, int>
     */
    private function diversifyDiscovery(array $rows): array
    {
        $categories = [];
        foreach ($rows as $rank => $row) {
            $categoryKey = (string) ((int) ($row['category_id'] ?? 0));
            $modelCode = trim((string) ($row['model_code'] ?? ''));
            if ($modelCode !== '' && preg_match('/^[^\s-]+/u', $modelCode, $match)) {
                $familyKey = strtoupper($match[0]);
            } else {
                $familyKey = $modelCode !== '' ? strtoupper($modelCode) : 'PRODUCT-' . (int) $row['id'];
            }

            if (!isset($categories[$categoryKey])) {
                $categories[$categoryKey] = ['rank' => $rank, 'families' => []];
            }
            if (!isset($categories[$categoryKey]['families'][$familyKey])) {
                $categories[$categoryKey]['families'][$familyKey] = ['rank' => $rank, 'ids' => []];
            }
            $categories[$categoryKey]['families'][$familyKey]['ids'][] = (int) $row['id'];
        }

        $categoryLists = [];
        foreach ($categories as $category) {
            $families = array_values($category['families']);
            usort($families, static fn (array $a, array $b): int => $a['rank'] <=> $b['rank']);
            $ids = [];
            $hasItems = true;
            while ($hasItems) {
                $hasItems = false;
                foreach ($families as &$family) {
                    if ($family['ids'] === []) {
                        continue;
                    }
                    $ids[] = array_shift($family['ids']);
                    $hasItems = true;
                }
                unset($family);
            }
            $categoryLists[] = ['rank' => $category['rank'], 'ids' => $ids];
        }

        usort($categoryLists, static fn (array $a, array $b): int => $a['rank'] <=> $b['rank']);
        $ordered = [];
        $hasItems = true;
        while ($hasItems) {
            $hasItems = false;
            foreach ($categoryLists as &$category) {
                if ($category['ids'] === []) {
                    continue;
                }
                $ordered[] = array_shift($category['ids']);
                $hasItems = true;
            }
            unset($category);
        }

        return $ordered;
    }

    /**
     * Hydrate a small page of ids without losing its curated sequence.
     *
     * @param array<int, int> $ids
     * @return array<int, Product>
     */
    private function hydrateOrderedIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $rows = Product::query()->connection()->select(
            'SELECT p.* FROM products p WHERE p.id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')',
            array_values($ids),
        );
        $products = [];
        foreach ($rows as $row) {
            $products[(int) $row['id']] = Product::fromRow($row);
        }

        $items = [];
        foreach ($ids as $id) {
            if (isset($products[$id])) {
                $items[] = $products[$id];
            }
        }
        Product::eagerLoad($items);

        return $items;
    }

    /**
     * @param array{locale?: string, search?: string, status?: string} $filters
     * @return Product[]
     */
    public function quickSearch(array $filters, int $limit = 8): array
    {
        $locale = $filters['locale'] ?? 'en';
        $queryTerm = (string) ($filters['search'] ?? '');
        $status = (string) ($filters['status'] ?? 'active');
        $categorySlug = trim((string) ($filters['category'] ?? ''));
        $searchValue = trim($queryTerm);

        if ($searchValue === '') {
            return [];
        }

        $connection = Product::query()->connection();
        $like = '%' . $searchValue . '%';

        $categoryClause = '';
        $categoryService = app(CategoryService::class);
        $categoryIds = $categorySlug !== ''
            ? $categoryService->descendantIdsBySlug($categorySlug)
            : [];
        if ($categorySlug !== '' && $categoryIds === []) {
            return [];
        }
        if ($categoryIds !== []) {
            $categoryClause = ' AND p.category_id IN ('
                . implode(',', array_fill(0, count($categoryIds), '?')) . ')';
        }

        $params = [$status, $like, $like, $like, $like, $like];
        if ($categoryIds !== []) {
            $params = [...$params, ...$categoryIds];
        }
        $rows = $connection->select(
            "SELECT DISTINCT p.*
             FROM products p
             WHERE p.deleted_at IS NULL
               AND p.status = ?
               AND " . ProductVisibility::mediaReadySql('p') . "
               AND (
                   LOWER(p.model_code) LIKE LOWER(?)
                   OR EXISTS (
                       SELECT 1 FROM product_variants pv
                       WHERE pv.product_id = p.id
                         AND pv.deleted_at IS NULL
                         AND LOWER(pv.sku) LIKE LOWER(?)
                   )
                   OR EXISTS (
                       SELECT 1 FROM product_translations pt
                       WHERE pt.product_id = p.id
                         AND (
                             LOWER(pt.name) LIKE LOWER(?)
                             OR LOWER(pt.description) LIKE LOWER(?)
                         )
                   )
                   OR EXISTS (
                       SELECT 1 FROM product_search_keywords pk
                       WHERE pk.product_id = p.id
                         AND LOWER(pk.keyword) LIKE LOWER(?)
                   )
               )
               {$categoryClause}
             ORDER BY p.id DESC",
            $params,
        );

        $rows = app(ProductColorFamilyService::class)->collapseRows($rows);
        $rows = array_slice($rows, 0, max(1, $limit));

        $items = array_map(static fn (array $row): Product => Product::fromRow($row), $rows);
        Product::eagerLoad($items);

        return $items;
    }
}
