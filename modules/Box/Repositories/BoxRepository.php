<?php

declare(strict_types=1);

namespace Modules\Box\Repositories;

use App\Repositories\Repository;
use Modules\Box\Models\Box;
use Modules\Box\Models\BoxItem;
use Modules\Products\Models\Product;

class BoxRepository extends Repository
{
    protected string $model = Box::class;

    public function findByToken(string $token): ?Box
    {
        if ($token === '') {
            return null;
        }

        $box = Box::query()->where('token', $token)->first();

        return $box;
    }

    public function productIds(Box $box): array
    {
        return array_map(
            static fn (array $row): int => (int) $row['product_id'],
            BoxItem::query()->connection()->select(
                'SELECT product_id FROM box_items WHERE box_id = ? ORDER BY id DESC',
                [(int) $box->getKey()],
            ),
        );
    }

    public function countItems(Box $box): int
    {
        return BoxItem::query()->where('box_id', (int) $box->getKey())->count();
    }

    public function has(Box $box, int $productId): bool
    {
        return BoxItem::query()
            ->where('box_id', (int) $box->getKey())
            ->where('product_id', $productId)
            ->exists();
    }

    public function addProduct(Box $box, int $productId): bool
    {
        if ($this->has($box, $productId)) {
            return false;
        }

        BoxItem::create([
            'box_id' => (int) $box->getKey(),
            'product_id' => $productId,
            'sort_order' => $this->countItems($box) + 1,
        ]);

        return true;
    }

    public function removeProduct(Box $box, int $productId): bool
    {
        return BoxItem::query()
            ->where('box_id', (int) $box->getKey())
            ->where('product_id', $productId)
            ->delete() > 0;
    }

    public function clearItems(Box $box): int
    {
        return BoxItem::query()->where('box_id', (int) $box->getKey())->delete();
    }

    public function items(Box $box, string $locale): array
    {
        $ids = $this->productIds($box);

        if ($ids === []) {
            return [];
        }

        $models = Product::query()
            ->whereIn('id', $ids)
            ->where('status', 'active')
            ->get();

        $byId = [];
        foreach ($models as $model) {
            $byId[(int) $model->getKey()] = $model;
        }

        $ordered = [];
        foreach ($ids as $id) {
            if (isset($byId[$id])) {
                $ordered[] = $byId[$id];
            }
        }

        if ($ordered === []) {
            return [];
        }

        Product::eagerLoad($ordered);

        $items = [];
        foreach ($ordered as $index => $model) {
            $translated = $model->translate($locale);
            $translated['box_index'] = $index + 1;
            $translated['box_url'] = route('products.show', ['slug' => (string) ($translated['slug'] ?? $model->slug)]);
            $items[] = $translated;
        }

        return $items;
    }
}
