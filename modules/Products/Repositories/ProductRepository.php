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
        /** @var Product|null $product */
        $product = Product::query()->where('slug', $slug)->first();

        return $product;
    }

    /**
     * @param array{status?: string, search?: string, locale?: string} $filters
     */
    public function search(array $filters, int $page = 1, int $perPage = 12): Paginator
    {
        $locale = $filters['locale'] ?? 'en';
        $connection = Product::query()->connection();

        $where = ['p.deleted_at IS NULL'];
        $bindings = [];

        if (($filters['status'] ?? '') !== '') {
            $where[] = 'p.status = ?';
            $bindings[] = $filters['status'];
        }

        if (!empty($filters['category_id'])) {
            $where[] = 'p.category_id = ?';
            $bindings[] = (int) $filters['category_id'];
        }

        $searchJoin = '';
        if (!empty($filters['category_slug'])) {
            $searchJoin .= ' INNER JOIN categories c ON c.id = p.category_id AND c.slug = ?';
            $bindings[] = (string) $filters['category_slug'];
        }
        if (($filters['search'] ?? '') !== '') {
            $searchJoin = "INNER JOIN product_translations pt
                ON pt.product_id = p.id AND pt.locale = ?";
            $bindings[] = $locale;
            $where[] = 'pt.name LIKE ?';
            $bindings[] = '%' . $filters['search'] . '%';
        }

        $whereSql = ' WHERE ' . implode(' AND ', $where);

        $total = (int) ($connection->selectOne(
            "SELECT COUNT(DISTINCT p.id) AS aggregate FROM products p {$searchJoin} {$whereSql}",
            $bindings,
        )['aggregate'] ?? 0);

        $rows = $connection->select(
            "SELECT DISTINCT p.* FROM products p {$searchJoin} {$whereSql} ORDER BY p.id DESC LIMIT {$perPage} OFFSET " . (($page - 1) * $perPage),
            $bindings,
        );

        $items = array_map(static fn (array $row): Product => Product::fromRow($row), $rows);

        return new Paginator($items, $total, $page, $perPage);
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
        $searchValue = trim($queryTerm);

        if ($searchValue === '') {
            return [];
        }

        $connection = Product::query()->connection();
        $like = '%' . $searchValue . '%';

        $rows = $connection->select(
            "SELECT DISTINCT p.*
             FROM products p
             WHERE p.deleted_at IS NULL
               AND p.status = ?
               AND (
                   LOWER(p.sku) LIKE LOWER(?)
                   OR EXISTS (
                       SELECT 1 FROM product_translations pt
                       WHERE pt.product_id = p.id
                         AND (
                             LOWER(pt.name) LIKE LOWER(?)
                             OR LOWER(pt.description) LIKE LOWER(?)
                         )
                   )
               )
             ORDER BY p.id DESC
             LIMIT ?",
              [$status, $like, $like, $like, (int) $limit],
        );

        return array_map(static fn (array $row): Product => Product::fromRow($row), $rows);
    }
}
