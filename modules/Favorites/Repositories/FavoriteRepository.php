<?php

declare(strict_types=1);

namespace Modules\Favorites\Repositories;

use App\Repositories\Repository;
use Core\Database\Paginator;
use Modules\Favorites\Models\Favorite;
use Modules\Favorites\Models\FavoriteItem;

class FavoriteRepository extends Repository
{
    protected string $model = Favorite::class;

    public function findByToken(string $token): ?Favorite
    {
        if ($token === '') {
            return null;
        }

        $favorite = Favorite::query()->where('token', $token)->first();

        return $favorite;
    }

    public function findByEmail(string $email): ?Favorite
    {
        $favorite = Favorite::query()
            ->where('email', mb_strtolower(trim($email)))
            ->orderBy('id', 'desc')
            ->first();

        return $favorite instanceof Favorite ? $favorite : null;
    }

    public function productIds(Favorite $favorite): array
    {
        return array_map(
            static fn (array $row): int => (int) $row['product_id'],
            FavoriteItem::query()->connection()->select(
                'SELECT product_id FROM favorite_items WHERE favorite_id = ? ORDER BY id DESC',
                [(int) $favorite->getKey()],
            ),
        );
    }

    public function countItems(Favorite $favorite): int
    {
        return FavoriteItem::query()->where('favorite_id', (int) $favorite->getKey())->count();
    }

    public function has(Favorite $favorite, int $productId): bool
    {
        return FavoriteItem::query()
            ->where('favorite_id', (int) $favorite->getKey())
            ->where('product_id', $productId)
            ->exists();
    }

    public function addProduct(Favorite $favorite, int $productId): bool
    {
        if ($this->has($favorite, $productId)) {
            return false;
        }

        FavoriteItem::create([
            'favorite_id' => (int) $favorite->getKey(),
            'product_id' => $productId,
            'sort_order' => $this->countItems($favorite) + 1,
        ]);

        return true;
    }

    public function removeProduct(Favorite $favorite, int $productId): bool
    {
        $deleted = FavoriteItem::query()
            ->where('favorite_id', (int) $favorite->getKey())
            ->where('product_id', $productId)
            ->delete();

        return $deleted > 0;
    }

    public function clearItems(Favorite $favorite): int
    {
        return FavoriteItem::query()
            ->where('favorite_id', (int) $favorite->getKey())
            ->delete();
    }

    public function itemRows(Favorite $favorite): array
    {
        return FavoriteItem::query()->connection()->select(
            'SELECT favorite_id, product_id, created_at FROM favorite_items
              WHERE favorite_id = ? ORDER BY id DESC',
            [(int) $favorite->getKey()],
        );
    }

    public function paginate(array $filters = [], int $page = 1, int $perPage = 20): Paginator
    {
        $builder = Favorite::query();

        $email = trim((string) ($filters['email'] ?? ''));
        if ($email !== '') {
            $builder->whereLike('email', '%' . $email . '%');
        }

        return $builder->orderBy('id', 'desc')->paginate($page, $perPage);
    }
}
