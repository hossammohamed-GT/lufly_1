<?php

declare(strict_types=1);

namespace Modules\Products\Repositories;

use App\Repositories\Repository;
use Core\Database\Paginator;
use Modules\Products\Models\Product;

class ProductRepository extends Repository
{
    protected string $model = Product::class;

    protected bool $auditing = true;

    protected string $auditEntity = 'product';

    public function findBySlug(string $slug): ?Product
    {
        $product = Product::query()->where('slug', $slug)->first();

        return $product;
    }

    public function search(array $filters, int $page = 1, int $perPage = 12): Paginator
    {
        $locale = $filters['locale'] ?? 'en';
        $connection = Product::query()->connection();

        $joins = [
            'name' => ' INNER JOIN product_translations pt ON pt.product_id = p.id AND pt.locale = ?',
            'cat' => ' INNER JOIN categories c ON c.id = p.category_id AND c.slug = ?',
        ];
        $usedJoins = [];
        $joinBindings = [];
        $where = ['p.deleted_at IS NULL'];
        $whereBindings = [];

        if (($filters['status'] ?? '') !== '') {
            $where[] = 'p.status = ?';
            $whereBindings[] = $filters['status'];
        }

        if (!empty($filters['category_id'])) {
            $where[] = 'p.category_id = ?';
            $whereBindings[] = (int) $filters['category_id'];
        }

        if (!empty($filters['category_slug'])) {
            $usedJoins[] = 'cat';
            $joinBindings[] = (string) $filters['category_slug'];
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

        $sql = "SELECT DISTINCT p.* FROM products p {$searchJoin} {$whereSql}{$orderBySql}";

        $total = (int) ($connection->selectOne(
            "SELECT COUNT(DISTINCT p.id) AS aggregate FROM products p {$searchJoin} {$whereSql}",
            array_merge($joinBindings, $whereBindings),
        )['aggregate'] ?? 0);

        $rows = $connection->select(
            $sql . ' LIMIT ' . max(1, $perPage) . ' OFFSET ' . (($page - 1) * $perPage),
            array_merge($joinBindings, $whereBindings),
        );

        $items = array_map(static fn (array $row): Product => Product::fromRow($row), $rows);
        Product::eagerLoad($items);

        return new Paginator($items, $total, $page, $perPage);
    }

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
        if ($categorySlug !== '') {
            $categoryClause = ' AND EXISTS (
                   SELECT 1 FROM categories c
                   WHERE c.id = p.category_id
                     AND c.deleted_at IS NULL
                     AND c.slug = ?
               )';
        }

        $params = [$status, $like, $like, $like, $like, $like];
        if ($categoryClause !== '') {
            $params[] = $categorySlug;
        }
        $params[] = (int) $limit;

        $rows = $connection->select(
            "SELECT DISTINCT p.*
             FROM products p
             WHERE p.deleted_at IS NULL
               AND p.status = ?
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
             ORDER BY p.id DESC
             LIMIT ?",
            $params,
        );

        $items = array_map(static fn (array $row): Product => Product::fromRow($row), $rows);
        Product::eagerLoad($items);

        return $items;
    }
}
