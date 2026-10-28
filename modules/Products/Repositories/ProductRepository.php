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

        $sort = (string) ($filters['sort'] ?? 'curated');
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
            'model' => ' ORDER BY p.is_featured DESC, p.sort_order ASC, p.model_code ASC, p.id DESC',
            'name' => " ORDER BY p.is_featured DESC, p.sort_order ASC, {$nameCollate}, p.id DESC",
            default => ' ORDER BY p.is_featured DESC, p.sort_order ASC, p.id DESC',
        };

        if ($sort === 'curated') {
            /* Featured products ALWAYS appear strictly first in Admin priority
               order (sort_order ASC, id ASC) on All Products, every Category,
               every Subcategory, and search; non-featured items rotate cleanly
               across categories and model families behind them. */
            $discoveryRows = $connection->select(
                "SELECT DISTINCT p.id, p.category_id, p.model_code, p.is_featured, p.sort_order,
                        CASE WHEN EXISTS (
                            SELECT 1 FROM product_media pm
                            INNER JOIN media m ON m.id = pm.media_id
                            WHERE pm.product_id = p.id
                              AND pm.variant_id IS NULL
                              AND pm.type NOT IN ('drawing', 'situ')
                              AND m.status = 'active'
                        ) THEN 1 ELSE 0 END AS has_photo
                 FROM products p {$searchJoin} {$whereSql}
                 ORDER BY p.is_featured DESC, p.sort_order ASC, has_photo DESC, p.id DESC",
                array_merge($joinBindings, $whereBindings),
            );

            if ($colorFamilies !== null) {
                $discoveryRows = $colorFamilies->collapseRows($discoveryRows);
            }

            $featuredRows = [];
            $regularRows = [];
            foreach ($discoveryRows as $dRow) {
                if ((int) ($dRow['is_featured'] ?? 0) === 1) {
                    $featuredRows[] = $dRow;
                } else {
                    $regularRows[] = $dRow;
                }
            }
            usort(
                $featuredRows,
                static fn (array $a, array $b): int => ((int) ($a['sort_order'] ?? PHP_INT_MAX) <=> (int) ($b['sort_order'] ?? PHP_INT_MAX))
                    ?: ((int) ($a['id'] ?? 0) <=> (int) ($b['id'] ?? 0)),
            );
            $featuredIds = array_map(static fn (array $r): int => (int) $r['id'], $featuredRows);
            $orderedIds = array_merge($featuredIds, $this->diversifyDiscovery($regularRows));
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
             ORDER BY p.is_featured DESC, p.sort_order ASC, p.id DESC",
            $params,
        );

        $rows = app(ProductColorFamilyService::class)->collapseRows($rows);
        $rows = array_slice($rows, 0, max(1, $limit));

        $items = array_map(static fn (array $row): Product => Product::fromRow($row), $rows);
        Product::eagerLoad($items);

        return $items;
    }

    /**
     * Resolve an uploaded technical datasheet PDF URL when one exists in
     * product_documents; returns null while datasheets are not yet published.
     */
    public function datasheetUrl(int $productId, string $locale = 'en'): ?string
    {
        if ($productId <= 0) {
            return null;
        }

        $connection = Product::query()->connection();
        $row = $connection->selectOne(
            "SELECT m.path
             FROM product_documents pd
             INNER JOIN media m ON m.id = pd.media_id
             WHERE pd.product_id = ?
               AND pd.variant_id IS NULL
               AND pd.document_type IN ('datasheet', 'data_sheet', 'technical_sheet')
               AND m.status = 'active'
             ORDER BY CASE WHEN pd.language = ? THEN 0 WHEN pd.language = 'en' THEN 1 ELSE 2 END,
                      pd.sort_order ASC,
                      pd.id ASC
             LIMIT 1",
            [$productId, $locale],
        );

        $path = trim((string) ($row['path'] ?? ''));

        return $path !== '' ? $path : null;
    }

    /**
     * Resolve 1-based position and prev/next product slugs within the current
     * leaf category (collapsed by color family) for the top showroom stepper.
     *
     * @param array<string, mixed> $product
     * @return array{position: int, total: int, prev_slug: ?string, next_slug: ?string}|null
     */
    public function categoryNeighbors(array $product): ?array
    {
        $productId = (int) ($product['id'] ?? 0);
        $categoryId = (int) ($product['category_id'] ?? 0);
        if ($productId <= 0 || $categoryId <= 0) {
            return null;
        }

        $familyIds = [$productId => true];
        foreach ((array) ($product['color_variants'] ?? []) as $cv) {
            $cvId = (int) ($cv['id'] ?? 0);
            if ($cvId > 0) {
                $familyIds[$cvId] = true;
            }
        }

        $connection = Product::query()->connection();
        $rows = $connection->select(
            "SELECT p.id, p.slug, p.is_featured, p.sort_order
             FROM products p
             WHERE p.deleted_at IS NULL
               AND p.status = ?
               AND p.category_id = ?
               AND " . ProductVisibility::mediaReadySql('p') . "
             ORDER BY p.is_featured DESC, p.sort_order ASC, p.id DESC",
            ['active', $categoryId],
        );

        $collapsed = app(ProductColorFamilyService::class)->collapseRows($rows);
        $total = count($collapsed);
        if ($total < 1) {
            return null;
        }

        $currentIndex = 0;
        foreach ($collapsed as $idx => $r) {
            if (isset($familyIds[(int) ($r['id'] ?? 0)])) {
                $currentIndex = $idx;
                break;
            }
        }

        $prevSlug = $total > 1 ? (string) ($collapsed[($currentIndex - 1 + $total) % $total]['slug'] ?? '') : null;
        $nextSlug = $total > 1 ? (string) ($collapsed[($currentIndex + 1) % $total]['slug'] ?? '') : null;

        return [
            'position' => $currentIndex + 1,
            'total' => $total,
            'prev_slug' => $prevSlug !== '' ? $prevSlug : null,
            'next_slug' => $nextSlug !== '' ? $nextSlug : null,
        ];
    }

    /**
     * Curated two-tier marketplace discovery for a product page:
     *  1. `similar`       — other collapsed product families in the same leaf
     *                       (topped up from sibling leaves under the same parent/root).
     *  2. `complementary` — finish-harmonized fixtures & accessories from
     *                       paired categories that complete the space.
     *
     * @param array<string, mixed> $product
     * @return array{similar: Product[], complementary: Product[]}
     */
    public function marketplaceRecommendations(
        array $product,
        int $similarLimit = 4,
        int $complementaryLimit = 4,
    ): array {
        $productId = (int) ($product['id'] ?? 0);
        if ($productId <= 0) {
            return ['similar' => [], 'complementary' => []];
        }

        $excluded = [$productId => true];
        foreach ((array) ($product['color_variants'] ?? []) as $cv) {
            $cvId = (int) ($cv['id'] ?? 0);
            if ($cvId > 0) {
                $excluded[$cvId] = true;
            }
        }
        foreach ((array) ($product['seat_covers'] ?? []) as $sc) {
            $scId = (int) ($sc['id'] ?? 0);
            if ($scId > 0) {
                $excluded[$scId] = true;
            }
        }

        $connection = Product::query()->connection();
        $colorFamilies = app(ProductColorFamilyService::class);
        $categoryService = app(CategoryService::class);
        $categoryId = (int) ($product['category_id'] ?? 0);
        $categorySlug = trim((string) ($product['category_slug'] ?? ''));
        $modelCode = trim((string) ($product['model_code'] ?? ''));
        $modelPrefix = '';
        if ($modelCode !== '' && preg_match('/^([A-Za-z0-9]{3,5})[-]/', $modelCode, $mPrefix) === 1) {
            $modelPrefix = strtoupper($mPrefix[1]);
        }

        $finishSpec = '';
        foreach ((array) ($product['specs'] ?? []) as $sRow) {
            $rk = strtolower(trim((string) ($sRow['raw_key'] ?? $sRow['spec_key'] ?? '')));
            if (in_array($rk, ['finish', 'color', 'colour'], true)) {
                $finishSpec = strtolower(trim((string) ($sRow['raw_value'] ?? $sRow['spec_value'] ?? '')));
                break;
            }
        }

        $fetchCandidates = static function (array $catIds, array $excludeMap) use ($connection, $colorFamilies): array {
            $catIds = array_values(array_unique(array_filter(array_map('intval', $catIds), static fn (int $v): bool => $v > 0)));
            if ($catIds === []) {
                return [];
            }

            $catPlaceholders = implode(',', array_fill(0, count($catIds), '?'));
            $params = ['active', ...$catIds];
            $excludeSql = '';
            $excludeIds = array_keys($excludeMap);
            if ($excludeIds !== []) {
                $excludeSql = ' AND p.id NOT IN (' . implode(',', array_fill(0, count($excludeIds), '?')) . ')';
                $params = [...$params, ...$excludeIds];
            }

            $rows = $connection->select(
                "SELECT p.id, p.category_id, p.model_code, p.is_featured, p.sort_order,
                        (SELECT ps.spec_value FROM product_specifications ps
                         WHERE ps.product_id = p.id AND ps.variant_id IS NULL AND ps.spec_key = 'Finish'
                         ORDER BY ps.id ASC LIMIT 1) AS finish_raw,
                        CASE WHEN EXISTS (
                            SELECT 1 FROM product_media pm
                            INNER JOIN media m ON m.id = pm.media_id
                            WHERE pm.product_id = p.id
                              AND pm.variant_id IS NULL
                              AND pm.type NOT IN ('drawing', 'situ')
                              AND m.status = 'active'
                        ) THEN 1 ELSE 0 END AS has_photo
                 FROM products p
                 WHERE p.deleted_at IS NULL
                   AND p.status = ?
                   AND p.category_id IN ({$catPlaceholders})
                   {$excludeSql}
                   AND " . ProductVisibility::mediaReadySql('p') . "
                 ORDER BY p.is_featured DESC, p.sort_order ASC, has_photo DESC, p.id DESC",
                $params,
            );

            return $colorFamilies->collapseRows($rows);
        };

        /* ---- Tier 1: Similar in same leaf category (topped up from parent branch) ---- */
        $similarIds = [];
        if ($categoryId > 0) {
            $sameLeafRows = $fetchCandidates([$categoryId], $excluded);
            usort(
                $sameLeafRows,
                static function (array $a, array $b) use ($modelPrefix, $finishSpec): int {
                    $aPrefix = ($modelPrefix !== '' && str_starts_with(strtoupper((string) ($a['model_code'] ?? '')), $modelPrefix . '-')) ? 1 : 0;
                    $bPrefix = ($modelPrefix !== '' && str_starts_with(strtoupper((string) ($b['model_code'] ?? '')), $modelPrefix . '-')) ? 1 : 0;
                    if ($aPrefix !== $bPrefix) {
                        return $bPrefix <=> $aPrefix;
                    }
                    $aFin = ($finishSpec !== '' && strtolower((string) ($a['finish_raw'] ?? '')) === $finishSpec) ? 1 : 0;
                    $bFin = ($finishSpec !== '' && strtolower((string) ($b['finish_raw'] ?? '')) === $finishSpec) ? 1 : 0;
                    if ($aFin !== $bFin) {
                        return $bFin <=> $aFin;
                    }
                    $aFeat = (int) ($a['is_featured'] ?? 0);
                    $bFeat = (int) ($b['is_featured'] ?? 0);
                    if ($aFeat !== $bFeat) {
                        return $bFeat <=> $aFeat;
                    }

                    return ((int) ($b['has_photo'] ?? 0) <=> (int) ($a['has_photo'] ?? 0))
                        ?: ((int) ($b['id'] ?? 0) <=> (int) ($a['id'] ?? 0));
                },
            );

            foreach (array_slice($sameLeafRows, 0, $similarLimit) as $r) {
                $rid = (int) $r['id'];
                $similarIds[] = $rid;
                $excluded[$rid] = true;
            }
        }

        if (count($similarIds) < $similarLimit && $categorySlug !== '') {
            $parts = explode('.', $categorySlug);
            for ($len = count($parts) - 1; $len >= 1 && count($similarIds) < $similarLimit; $len--) {
                $ancestorSlug = implode('.', array_slice($parts, 0, $len));
                $branchCatIds = $categoryService->descendantIdsBySlug($ancestorSlug);
                $siblingRows = $fetchCandidates($branchCatIds, $excluded);
                foreach ($this->diversifyDiscovery($siblingRows) as $sid) {
                    if (!isset($excluded[$sid])) {
                        $similarIds[] = $sid;
                        $excluded[$sid] = true;
                        if (count($similarIds) >= $similarLimit) {
                            break;
                        }
                    }
                }
            }
        }

        /* ---- Tier 2: Complementary pieces that complete the space ---- */
        $compBranches = $this->complementaryCategorySlugs($categorySlug);
        $compCatIds = [];
        foreach ($compBranches as $bSlug) {
            foreach ($categoryService->descendantIdsBySlug($bSlug) as $cid) {
                if ($cid !== $categoryId) {
                    $compCatIds[$cid] = $cid;
                }
            }
        }

        $complementaryIds = [];
        if ($compCatIds !== []) {
            $compRows = $fetchCandidates(array_values($compCatIds), $excluded);

            /* Prioritize finish-harmonized pieces first so e.g. a Matte Black or
               Rose Gold mixer pairs naturally with matching accessories/showers. */
            $finishTokens = array_values(array_filter(
                preg_split('#\s*/\s*#', $finishSpec) ?: [],
                static fn (string $t): bool => $t !== '',
            ));
            usort(
                $compRows,
                static function (array $a, array $b) use ($finishTokens): int {
                    $scoreFn = static function (string $candFin) use ($finishTokens): int {
                        if ($candFin === '' || $finishTokens === []) {
                            return 0;
                        }
                        foreach ($finishTokens as $tok) {
                            if (str_contains($candFin, $tok)) {
                                return 2;
                            }
                        }
                        return 0;
                    };
                    $sa = $scoreFn(strtolower((string) ($a['finish_raw'] ?? '')));
                    $sb = $scoreFn(strtolower((string) ($b['finish_raw'] ?? '')));
                    if ($sa !== $sb) {
                        return $sb <=> $sa;
                    }
                    $aFeat = (int) ($a['is_featured'] ?? 0);
                    $bFeat = (int) ($b['is_featured'] ?? 0);
                    if ($aFeat !== $bFeat) {
                        return $bFeat <=> $aFeat;
                    }

                    return ((int) ($b['has_photo'] ?? 0) <=> (int) ($a['has_photo'] ?? 0))
                        ?: ((int) ($a['sort_order'] ?? 0) <=> (int) ($b['sort_order'] ?? 0));
                },
            );

            foreach (array_slice($this->diversifyDiscovery($compRows), 0, $complementaryLimit) as $cid) {
                $complementaryIds[] = $cid;
            }
        }

        return [
            'similar' => $this->hydrateOrderedIds($similarIds),
            'complementary' => $this->hydrateOrderedIds($complementaryIds),
        ];
    }

    /**
     * Map any category slug to complementary architectural branches.
     *
     * @return string[]
     */
    private function complementaryCategorySlugs(string $categorySlug): array
    {
        if (str_starts_with($categorySlug, 'kitchen.kitchen-mixers')) {
            return ['kitchen.sinks', 'bathroom.angle-valves', 'drains'];
        }
        if (str_starts_with($categorySlug, 'kitchen.sinks') || str_starts_with($categorySlug, 'kitchen')) {
            return ['kitchen.kitchen-mixers', 'drains', 'bathroom.angle-valves'];
        }
        if (str_starts_with($categorySlug, 'kids.kids-sanitary-ware')) {
            return ['kids.kids-mixers', 'mirrors'];
        }
        if (str_starts_with($categorySlug, 'kids')) {
            return ['kids.kids-sanitary-ware', 'mirrors'];
        }
        if (str_starts_with($categorySlug, 'accessibility.accessible-sanitary-ware')) {
            return ['accessibility.kids-bars-supports', 'accessibility.accessible-accessories', 'bathroom.mixers'];
        }
        if (str_starts_with($categorySlug, 'accessibility')) {
            return ['accessibility.accessible-sanitary-ware', 'accessibility.accessible-accessories', 'accessibility.kids-bars-supports'];
        }
        if (str_starts_with($categorySlug, 'vanity-units')) {
            return ['mirrors', 'bathroom.mixers', 'bathroom.accessories'];
        }
        if (str_starts_with($categorySlug, 'mirrors')) {
            return ['vanity-units', 'bathroom.mixers', 'bathroom.sanitary-ware', 'bathroom.accessories'];
        }
        if (str_starts_with($categorySlug, 'drains') || str_starts_with($categorySlug, 'shower-enclosures')) {
            return ['bathroom.mixers', 'bathroom.sanitary-ware', 'bathroom.accessories'];
        }
        if (str_starts_with($categorySlug, 'drinking-fountains')) {
            return ['bathroom.accessories', 'drains', 'kitchen.sinks'];
        }
        if (str_starts_with($categorySlug, 'bathroom.sanitary-ware')) {
            return ['bathroom.mixers', 'bathroom.accessories', 'mirrors', 'bathroom.angle-valves'];
        }
        if (str_starts_with($categorySlug, 'bathroom.mixers')) {
            return ['bathroom.sanitary-ware', 'bathroom.accessories', 'mirrors', 'bathroom.angle-valves'];
        }
        if (str_starts_with($categorySlug, 'bathroom.accessories')) {
            return ['bathroom.mixers', 'mirrors', 'bathroom.sanitary-ware', 'vanity-units'];
        }
        if (str_starts_with($categorySlug, 'bathroom.angle-valves')) {
            return ['bathroom.mixers', 'bathroom.sanitary-ware', 'kitchen.kitchen-mixers'];
        }

        return ['bathroom.mixers', 'bathroom.sanitary-ware', 'bathroom.accessories', 'mirrors'];
    }
}
