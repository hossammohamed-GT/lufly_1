<?php

declare(strict_types=1);

namespace Modules\Products\Services;

use App\Services\ActivityLogger;
use App\Services\LocalizationService;
use App\Services\SlugService;
use Core\Database\Paginator;
use Core\Exceptions\NotFoundException;
use Modules\Products\Models\Product;
use Modules\Products\Repositories\ProductRepository;

class ProductService
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly LocalizationService $localization,
        private readonly SlugService $slugs,
        private readonly ActivityLogger $activity,
    ) {
    }

    public function paginate(array $filters = [], int $page = 1, int $perPage = 12): Paginator
    {
        return $this->products->search($filters, $page, $perPage);
    }

    public function quickSearch(string $query, string $locale = 'en', int $limit = 8, ?string $categorySlug = null): array
    {
        $term = trim($query);
        if ($term === '') {
            return [];
        }

        $items = $this->products->quickSearch([
            'locale' => $locale,
            'status' => 'active',
            'search' => $term,
            'category' => (string) $categorySlug,
        ], $limit);

        return array_map(
            fn (Product $product): array => $product->translate($locale),
            $items,
        );
    }

    public function findTranslatedBySlug(string $slug, string $locale): array
    {
        $product = $this->products->findBySlug($slug);
        if ($product === null || !in_array((string) $product->status, ['active', 'coming_soon'], true)) {
            throw new NotFoundException(trans('errors.product_not_found'));
        }

        return $product->translate($locale);
    }

    public function find(int $id): Product
    {
        $product = $this->products->find($id);
        if ($product === null) {
            throw new NotFoundException(trans('errors.product_not_found'));
        }

        return $product;
    }

    public function findPublic(int $id): Product
    {
        $product = $this->find($id);
        if (!in_array((string) $product->status, ['active', 'coming_soon'], true)) {
            throw new NotFoundException(trans('errors.product_not_found'));
        }

        return $product;
    }

    public function create(array $data, array $translations): Product
    {
        return Product::query()->connection()->transaction(function () use ($data, $translations): Product {
            $modelCode = (string) ($data['model_code'] ?? '');

            $data['slug'] = $this->slugs->generate(
                (string) (($data['slug'] ?? '') ?: ($translations['en']['name'] ?? $modelCode ?: 'product')),
                'products',
            );

            $data['is_featured'] = (int) (($data['is_featured'] ?? '0') === '1');

            $product = $this->products->create($data);
            $this->localization->syncTranslations('product', $product->id, $translations);

            $this->syncDefaultVariant((int) $product->id, $modelCode, (float) ($data['price'] ?? 0));
            $this->syncSeoMeta((int) $product->id, $data);

            $this->activity->created('product', $product->id, ['slug' => $product->slug]);

            return $product;
        });
    }

    public function update(int $id, array $data, array $translations = []): Product
    {
        return Product::query()->connection()->transaction(function () use ($id, $data, $translations): Product {
            $product = $this->find($id);

            if (isset($data['slug']) && $data['slug'] !== '' && $data['slug'] !== $product->slug) {
                $data['slug'] = $this->slugs->generate((string) $data['slug'], 'products', 'slug', $id);
            } else {
                unset($data['slug']);
            }

            if (array_key_exists('is_featured', $data)) {
                $data['is_featured'] = (int) (($data['is_featured'] ?? '0') === '1');
            }

            $price = $data['price'] ?? null;
            $metaTitle = $data['meta_title'] ?? null;
            $metaDescription = $data['meta_description'] ?? null;
            $newModelCode = isset($data['model_code']) ? (string) $data['model_code'] : (string) $product->model_code;
            unset($data['price'], $data['meta_title'], $data['meta_description']);

            $this->products->update($id, $data);

            if ($translations !== []) {
                $this->localization->syncTranslations('product', $id, $translations);
            }

            $this->syncDefaultVariant($id, $newModelCode, $price !== null ? (float) $price : null);

            if ($metaTitle !== null || $metaDescription !== null) {
                $this->syncSeoMeta($id, [
                    'meta_title' => (string) $metaTitle,
                    'meta_description' => (string) $metaDescription,
                ]);
            }

            $this->activity->updated('product', $id);

            return $this->find($id);
        });
    }

    public function delete(int $id): void
    {
        Product::query()->connection()->transaction(function () use ($id): void {
            $this->products->delete($id);
            $this->activity->deleted('product', $id);
        });
    }

    private function syncDefaultVariant(int $productId, string $modelCode, ?float $price = null): void
    {
        $connection = Product::query()->connection();

        $variant = $connection->selectOne(
            'SELECT id, price FROM product_variants WHERE product_id = ? AND deleted_at IS NULL ORDER BY sort_order ASC, id ASC LIMIT 1',
            [$productId],
        );

        $sku = $modelCode !== '' ? $modelCode : ('LUFLY-' . $productId);

        if ($variant === null) {
            $connection->table('product_variants')->insert([
                'product_id' => $productId,
                'sku' => $sku,
                'variant_name' => null,
                'price' => $price ?? 0.0,
                'stock_status' => 'in_stock',
                'sort_order' => 1,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            return;
        }

        $updates = [
            'sku' => $sku,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($price !== null) {
            $updates['price'] = $price;
        }

        $connection->table('product_variants')->where('id', (int) $variant['id'])->update($updates);
    }

    private function syncSeoMeta(int $productId, array $data): void
    {
        $metaTitle = trim((string) ($data['meta_title'] ?? ''));
        $metaDescription = trim((string) ($data['meta_description'] ?? ''));

        if ($metaTitle === '' && $metaDescription === '') {
            return;
        }

        $connection = Product::query()->connection();
        $now = date('Y-m-d H:i:s');

        $existing = $connection->selectOne(
            'SELECT id FROM seo_meta WHERE product_id = ? AND locale = ? LIMIT 1',
            [$productId, 'en'],
        );

        if ($existing === null) {
            $connection->table('seo_meta')->insert([
                'product_id' => $productId,
                'locale' => 'en',
                'meta_title' => $metaTitle !== '' ? $metaTitle : null,
                'meta_description' => $metaDescription !== '' ? $metaDescription : null,
                'og_title' => null,
                'og_description' => null,
                'canonical_url' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return;
        }

        $connection->table('seo_meta')->where('id', (int) $existing['id'])->update([
            'meta_title' => $metaTitle !== '' ? $metaTitle : null,
            'meta_description' => $metaDescription !== '' ? $metaDescription : null,
            'updated_at' => $now,
        ]);
    }
}
