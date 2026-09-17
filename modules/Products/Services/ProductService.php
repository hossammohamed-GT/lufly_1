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
    public function quickSearch(string $query, string $locale = 'en', int $limit = 8): array
    {
        $term = trim($query);
        if ($term === '') {
            return [];
        }

        $items = $this->products->quickSearch([
            'locale' => $locale,
            'status' => 'active',
            'search' => $term,
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
        if ($product === null || $product->status !== 'active') {
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

    /**
     * @param array<string, mixed> $data core fields
     * @param array<string, array<string, mixed>> $translations locale => fields
     */
    public function create(array $data, array $translations): Product
    {
        $data['slug'] = $this->slugs->generate(
            (string) ($data['slug'] ?: ($translations['en']['name'] ?? 'product')),
            'products',
        );

        /** @var Product $product */
        $product = $this->products->create($data);
        $this->localization->syncTranslations('product', $product->id, $translations);
        $this->activity->created('product', $product->id, ['slug' => $product->slug]);

        return $product;
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, array<string, mixed>> $translations
     */
    public function update(int $id, array $data, array $translations = []): Product
    {
        $product = $this->find($id);

        if (isset($data['slug']) && $data['slug'] !== '' && $data['slug'] !== $product->slug) {
            $data['slug'] = $this->slugs->generate((string) $data['slug'], 'products', 'slug', $id);
        } else {
            unset($data['slug']);
        }

        $this->products->update($id, $data);

        if ($translations !== []) {
            $this->localization->syncTranslations('product', $id, $translations);
        }

        $this->activity->updated('product', $id);

        return $this->find($id);
    }

    public function delete(int $id): void
    {
        $this->products->delete($id);
        $this->activity->deleted('product', $id);
    }
}
