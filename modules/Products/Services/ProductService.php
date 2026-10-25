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

    /** @param array{status?: string, search?: string, locale?: string} $filters */
    public function paginate(array $filters = [], int $page = 1, int $perPage = 12): Paginator
    {
        return $this->products->search($filters, $page, $perPage);
    }

    /** @return array<int, array<string, mixed>> */
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

    /** @return array<string, mixed> */
    public function findTranslatedBySlug(string $slug, string $locale): array
    {
        $product = $this->products->findBySlug($slug);
        if ($product === null || !ProductVisibility::isPublic($product)) {
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

        /** @var Product $product */
        return $product;
    }

    public function findPublic(int $id): Product
    {
        $product = $this->find($id);
        if (!ProductVisibility::isPublic($product)) {
            throw new NotFoundException(trans('errors.product_not_found'));
        }

        return $product;
    }

    /**
     * @param array<string, mixed> $data core fields
     * @param array<string, array<string, mixed>> $translations locale => fields
     */
    public function create(array $data, array $translations): Product
    {
        return Product::query()->connection()->transaction(function () use ($data, $translations): Product {
            $modelCode = (string) ($data['model_code'] ?? '');

            $data['slug'] = $this->slugs->generate(
                (string) (($data['slug'] ?? '') ?: ($translations['en']['name'] ?? $modelCode ?: 'product')),
                'products',
            );

            $data['is_featured'] = (int) (($data['is_featured'] ?? '0') === '1');

            /** @var Product $product */
            $product = $this->products->create($data);
            $this->localization->syncTranslations('product', $product->id, $translations);

            $this->syncDefaultVariant((int) $product->id, $modelCode, (float) ($data['price'] ?? 0));
            $this->syncSeoMeta((int) $product->id, $data);
            $this->syncCategoryArtifacts(
                (int) $product->id,
                isset($product->category_id) ? (int) $product->category_id : null,
                $modelCode,
                (string) ($translations['en']['name'] ?? $modelCode),
            );

            $this->activity->created('product', $product->id, ['slug' => $product->slug]);

            return $product;
        });
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, array<string, mixed>> $translations
     */
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

            $updated = $this->find($id);
            $enName = (string) ($translations['en']['name'] ?? ($updated->translations()['en']['name'] ?? $newModelCode));
            $this->syncCategoryArtifacts(
                $id,
                isset($updated->category_id) ? (int) $updated->category_id : null,
                $newModelCode,
                $enName,
            );

            $this->activity->updated('product', $id);

            return $updated;
        });
    }

    public function delete(int $id): void
    {
        Product::query()->connection()->transaction(function () use ($id): void {
            $this->products->delete($id);
            $this->activity->deleted('product', $id);
        });
    }

    /**
     * Keep a single canonical default variant in sync so storefront pricing/SKU
     * stays coherent even before multi-variant features are managed directly
     * from their own screens as the catalog grows.
     */
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

    /** @param array<string, mixed> $data */
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

    private function syncCategoryArtifacts(
        int $productId,
        ?int $categoryId,
        string $modelCode,
        string $name,
    ): void {
        $connection = Product::query()->connection();
        $categorySlug = '';
        $categoryLabels = [];

        if ($categoryId !== null && $categoryId > 0) {
            $cat = $connection->selectOne(
                'SELECT slug FROM categories WHERE id = ? AND deleted_at IS NULL LIMIT 1',
                [$categoryId],
            );
            $categorySlug = trim((string) ($cat['slug'] ?? ''));
            foreach ($connection->select('SELECT name FROM category_translations WHERE category_id = ?', [$categoryId]) as $tr) {
                $label = trim((string) ($tr['name'] ?? ''));
                if ($label !== '') {
                    $categoryLabels[] = mb_strtolower($label);
                }
            }
        }

        $collectionName = $categorySlug !== '' ? $categorySlug : 'products';
        $connection->affect(
            'UPDATE media SET collection = ?, updated_at = ?
             WHERE id IN (SELECT media_id FROM product_media WHERE product_id = ?)',
            [$collectionName, date('Y-m-d H:i:s'), $productId],
        );

        $keywords = ['lufly'];
        if ($modelCode !== '') {
            $keywords[] = mb_strtolower($modelCode);
        }
        foreach (preg_split('/[^\p{L}\p{N}-]+/u', $name) ?: [] as $word) {
            if (mb_strlen($word) >= 3) {
                $keywords[] = mb_strtolower($word);
            }
        }
        if ($categorySlug !== '') {
            foreach (explode('.', $categorySlug) as $segment) {
                $segment = trim(str_replace('-', ' ', $segment));
                if ($segment !== '') {
                    $keywords[] = mb_strtolower($segment);
                }
            }
        }
        foreach ($categoryLabels as $label) {
            $keywords[] = $label;
        }

        $connection->table('product_search_keywords')->where('product_id', $productId)->delete();
        foreach (array_unique($keywords) as $keyword) {
            $connection->table('product_search_keywords')->insert([
                'product_id' => $productId,
                'keyword' => mb_substr($keyword, 0, 150),
            ]);
        }
    }
}
