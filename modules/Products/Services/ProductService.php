<?php

declare(strict_types=1);

namespace Modules\Products\Services;

use App\Services\ActivityLogger;
use App\Services\LocalizationService;
use App\Services\SlugService;
use Core\Database\Paginator;
use Core\Exceptions\NotFoundException;
use InvalidArgumentException;
use Modules\Products\Models\Product;
use Modules\Products\Repositories\ProductRepository;

class ProductService
{
    public const STATUSES = ['draft', 'active', 'hidden', 'discontinued', 'coming_soon'];
    public const STOCK_STATUSES = ['in_stock', 'out_of_stock', 'pre_order', 'made_to_order'];

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

    public function datasheetUrl(int $productId, string $locale = 'en'): ?string
    {
        return $this->products->datasheetUrl($productId, $locale);
    }

    /**
     * @param array<string, mixed> $product
     * @return array{position: int, total: int, prev_slug: ?string, next_slug: ?string}|null
     */
    public function categoryNeighbors(array $product): ?array
    {
        return $this->products->categoryNeighbors($product);
    }

    /**
     * @param array<string, mixed> $product
     * @return array{similar: Product[], complementary: Product[]}
     */
    public function marketplaceRecommendations(array $product, int $similarLimit = 4, int $complementaryLimit = 4): array
    {
        return $this->products->marketplaceRecommendations($product, $similarLimit, $complementaryLimit);
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
     * @param array<string, mixed> $extra optional variants/specs/features/dimensions/seo/relations payloads
     */
    public function create(array $data, array $translations, array $extra = []): Product
    {
        return Product::query()->connection()->transaction(function () use ($data, $translations, $extra): Product {
            $modelCode = (string) ($data['model_code'] ?? '');

            $data['slug'] = $this->slugs->generate(
                (string) (($data['slug'] ?? '') ?: ($translations['en']['name'] ?? $modelCode ?: 'product')),
                'products',
            );

            $data['is_featured'] = (int) (($data['is_featured'] ?? '0') === '1');

            /** @var Product $product */
            $product = $this->products->create($data);
            $productId = (int) $product->id;
            $this->localization->syncTranslations('product', $productId, $translations);

            $this->syncExtra($productId, $modelCode, $data, $extra);

            if (!array_key_exists('seo', $extra)) {
                $this->syncSeoMeta($productId, $data);
            }

            $this->syncCategoryArtifacts(
                $productId,
                isset($product->category_id) ? (int) $product->category_id : null,
                $modelCode,
                (string) ($translations['en']['name'] ?? $modelCode),
            );

            $this->activity->created('product', $productId, ['slug' => $product->slug]);
            Product::clearStaticCaches();

            return $product;
        });
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, array<string, mixed>> $translations
     * @param array<string, mixed> $extra optional variants/specs/features/dimensions/seo/relations payloads
     */
    public function update(int $id, array $data, array $translations = [], array $extra = []): Product
    {
        return Product::query()->connection()->transaction(function () use ($id, $data, $translations, $extra): Product {
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

            $this->syncExtra($id, $newModelCode, ['price' => $price], $extra);

            if (!array_key_exists('seo', $extra) && ($metaTitle !== null || $metaDescription !== null)) {
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
            Product::clearStaticCaches();

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

    /* ------------------------------------------------------- inline editing */

    /**
     * One-field update behind the admin product cards (click-to-edit).
     *
     * @param array{field?: string, value?: mixed, locale?: string, feature_id?: mixed, spec_id?: mixed} $payload
     * @return array<string, mixed> normalized result for the UI
     */
    public function inlineUpdate(int $id, array $payload): array
    {
        $product = $this->find($id);
        $connection = Product::query()->connection();
        $field = (string) ($payload['field'] ?? '');
        $value = $payload['value'] ?? '';
        $locale = $this->inlineLocale((string) ($payload['locale'] ?? 'en'));
        $fallback = (string) config('localization.fallback', 'en');
        $now = date('Y-m-d H:i:s');

        $result = $connection->transaction(function () use (
            $connection, $product, $id, $field, $value, $locale, $fallback, $payload, $now
        ): array {
            switch ($field) {
                case 'name':
                    $name = trim((string) $value);
                    if ($locale === $fallback && $name === '') {
                        throw new InvalidArgumentException(trans('products.inline_required'));
                    }
                    if (mb_strlen($name) > 255) {
                        throw new InvalidArgumentException(trans('products.inline_too_long'));
                    }
                    $this->localization->syncTranslations('product', $id, [$locale => ['name' => $name]]);
                    if ($locale === $fallback) {
                        $this->refreshArtifacts($id);
                    }
                    return ['value' => $name];

                case 'short_description':
                    $text = trim((string) $value);
                    if (mb_strlen($text) > 255) {
                        throw new InvalidArgumentException(trans('products.inline_too_long'));
                    }
                    $this->localization->syncTranslations('product', $id, [$locale => ['short_description' => $text]]);
                    return ['value' => $text];

                case 'model_code':
                    $code = trim((string) $value);
                    if ($code === '' || mb_strlen($code) > 100) {
                        throw new InvalidArgumentException(trans('products.inline_required'));
                    }
                    $taken = $connection->selectOne(
                        'SELECT id FROM products WHERE model_code = ? AND id != ? AND deleted_at IS NULL LIMIT 1',
                        [$code, $id],
                    );
                    if ($taken !== null) {
                        throw new InvalidArgumentException(trans('products.inline_model_taken'));
                    }
                    $oldCode = (string) ($product->model_code ?? '');
                    $variant = $this->defaultVariantRow($id);
                    if ($variant !== null && (string) $variant['sku'] === $oldCode && $code !== $oldCode) {
                        $skuTaken = $connection->selectOne(
                            'SELECT id FROM product_variants WHERE sku = ? AND id != ? LIMIT 1',
                            [$code, (int) $variant['id']],
                        );
                        if ($skuTaken !== null) {
                            throw new InvalidArgumentException(trans('products.variant_sku_taken', ['sku' => $code]));
                        }
                        $connection->table('product_variants')->where('id', (int) $variant['id'])->update([
                            'sku' => $code,
                            'updated_at' => $now,
                        ]);
                    }
                    $connection->table('products')->where('id', $id)->update([
                        'model_code' => $code,
                        'updated_at' => $now,
                    ]);
                    $this->refreshArtifacts($id);
                    return ['value' => $code];

                case 'slug':
                    $slug = $this->slugs->generate(trim((string) $value), 'products', 'slug', $id);
                    $connection->table('products')->where('id', $id)->update([
                        'slug' => $slug,
                        'updated_at' => $now,
                    ]);
                    return ['value' => $slug];

                case 'price':
                    if (!is_numeric($value) || (float) $value < 0) {
                        throw new InvalidArgumentException(trans('products.inline_price_invalid'));
                    }
                    $variant = $this->ensureDefaultVariant($id, (string) ($product->model_code ?? ''));
                    $connection->table('product_variants')->where('id', (int) $variant['id'])->update([
                        'price' => (float) $value,
                        'updated_at' => $now,
                    ]);
                    return ['value' => $this->trimNumber((float) $value)];

                case 'stock_status':
                    $stock = (string) $value;
                    if (!in_array($stock, self::STOCK_STATUSES, true)) {
                        throw new InvalidArgumentException(trans('products.inline_invalid_value'));
                    }
                    $variant = $this->ensureDefaultVariant($id, (string) ($product->model_code ?? ''));
                    $connection->table('product_variants')->where('id', (int) $variant['id'])->update([
                        'stock_status' => $stock,
                        'updated_at' => $now,
                    ]);
                    return ['value' => $stock];

                case 'status':
                    $status = (string) $value;
                    if (!in_array($status, self::STATUSES, true)) {
                        throw new InvalidArgumentException(trans('products.inline_invalid_value'));
                    }
                    $connection->table('products')->where('id', $id)->update([
                        'status' => $status,
                        'updated_at' => $now,
                    ]);
                    return ['value' => $status, 'label' => trans('common.' . $status)];

                case 'is_featured':
                    $featured = in_array($value, [1, '1', true], true) ? 1 : 0;
                    $updates = ['is_featured' => $featured, 'updated_at' => $now];
                    $sortOrder = (int) ($product->sort_order ?? 0);
                    if ($featured === 1 && $sortOrder === 0) {
                        $max = $connection->selectOne(
                            'SELECT MAX(sort_order) AS max_sort FROM products WHERE is_featured = 1 AND deleted_at IS NULL',
                        );
                        $sortOrder = (int) ($max['max_sort'] ?? 0) + 1;
                        $updates['sort_order'] = $sortOrder;
                    }
                    $connection->table('products')->where('id', $id)->update($updates);
                    return ['value' => $featured, 'sort_order' => $sortOrder];

                case 'is_new':
                    if (!$this->hasIsNewColumn()) {
                        throw new InvalidArgumentException('The "New" flag needs a database update first: run php cli migrate.');
                    }
                    $isNew = in_array($value, [1, '1', true], true) ? 1 : 0;
                    $connection->table('products')->where('id', $id)->update([
                        'is_new' => $isNew,
                        'updated_at' => $now,
                    ]);
                    return ['value' => $isNew];

                case 'sort_order':
                    if (!is_numeric($value) || (int) $value < 0) {
                        throw new InvalidArgumentException(trans('products.inline_invalid_value'));
                    }
                    $order = (int) $value;
                    $connection->table('products')->where('id', $id)->update([
                        'sort_order' => $order,
                        'updated_at' => $now,
                    ]);
                    return ['value' => $order];

                case 'category_id':
                    $categoryId = (int) $value;
                    if ($categoryId > 0 && !$this->isActiveLeaf($categoryId)) {
                        throw new InvalidArgumentException(trans('products.inline_category_leaf'));
                    }
                    $connection->table('products')->where('id', $id)->update([
                        'category_id' => $categoryId > 0 ? $categoryId : null,
                        'updated_at' => $now,
                    ]);
                    $this->refreshArtifacts($id);
                    return [
                        'value' => $categoryId,
                        'category_path' => $categoryId > 0 ? $this->categoryPathLabel($categoryId) : '',
                    ];

                case 'feature_value':
                case 'feature_label':
                    $featureId = (int) ($payload['feature_id'] ?? 0);
                    $feature = $featureId > 0 ? $connection->selectOne(
                        'SELECT id, preset_id FROM product_card_features WHERE id = ? AND product_id = ? LIMIT 1',
                        [$featureId, $id],
                    ) : null;
                    if ($feature === null) {
                        throw new InvalidArgumentException(trans('products.inline_invalid_value'));
                    }
                    $text = trim((string) $value);
                    $column = $field === 'feature_value' ? 'feature_value' : 'label';
                    if (mb_strlen($text) > ($column === 'label' ? 120 : 255)) {
                        throw new InvalidArgumentException(trans('products.inline_too_long'));
                    }
                    $existing = $connection->selectOne(
                        'SELECT id FROM product_card_feature_translations WHERE feature_id = ? AND locale = ? LIMIT 1',
                        [$featureId, $locale],
                    );
                    if ($existing !== null) {
                        $connection->table('product_card_feature_translations')->where('id', (int) $existing['id'])->update([$column => $text]);
                    } else {
                        $presetLabel = '';
                        if (!empty($feature['preset_id'])) {
                            $presetRow = $connection->selectOne(
                                'SELECT label FROM product_feature_preset_translations WHERE preset_id = ? AND locale = ? LIMIT 1',
                                [(int) $feature['preset_id'], $locale],
                            );
                            $presetLabel = (string) ($presetRow['label'] ?? '');
                        }
                        $connection->table('product_card_feature_translations')->insert([
                            'feature_id' => $featureId,
                            'locale' => $locale,
                            'label' => $column === 'label' ? $text : $presetLabel,
                            'feature_value' => $column === 'feature_value' ? $text : '',
                        ]);
                    }
                    return ['value' => $text];

                case 'card_feature':
                    /* Card-face feature editor: label + icon always come from
                       a ready-made preset — only the value is typed. */
                    $action = (string) ($payload['action'] ?? 'upsert');
                    $featureId = (int) ($payload['feature_id'] ?? 0);
                    $feature = $featureId > 0 ? $connection->selectOne(
                        'SELECT id FROM product_card_features WHERE id = ? AND product_id = ? LIMIT 1',
                        [$featureId, $id],
                    ) : null;

                    if ($action === 'delete') {
                        if ($feature === null) {
                            throw new InvalidArgumentException(trans('products.inline_invalid_value'));
                        }
                        $connection->affect('DELETE FROM product_card_feature_translations WHERE feature_id = ?', [$featureId]);
                        $connection->affect('DELETE FROM product_card_features WHERE id = ?', [$featureId]);
                        foreach (array_values($connection->select(
                            'SELECT id FROM product_card_features WHERE product_id = ? ORDER BY sort_order ASC, id ASC',
                            [$id],
                        )) as $index => $remaining) {
                            $connection->table('product_card_features')->where('id', (int) $remaining['id'])
                                ->update(['sort_order' => $index + 1]);
                        }
                        return ['value' => 'deleted', 'feature_id' => $featureId];
                    }

                    $presetId = (int) ($payload['preset_id'] ?? 0);
                    $preset = null;
                    foreach ($this->featurePresets() as $candidate) {
                        if ((int) $candidate['id'] === $presetId) {
                            $preset = $candidate;
                            break;
                        }
                    }
                    if ($preset === null) {
                        throw new InvalidArgumentException(trans('products.feature_preset_required'));
                    }
                    $text = trim((string) $value);
                    if (mb_strlen($text) > 255) {
                        throw new InvalidArgumentException(trans('products.inline_too_long'));
                    }
                    $icon = ProductFeatureIconCatalog::normalize((string) $preset['icon_key']);

                    if ($feature !== null) {
                        $connection->table('product_card_features')->where('id', $featureId)->update([
                            'preset_id' => $presetId,
                            'icon_key' => $icon,
                            'updated_at' => $now,
                        ]);
                    } else {
                        $sizes = $connection->selectOne(
                            'SELECT COUNT(*) AS total, COALESCE(MAX(sort_order), 0) AS max_sort FROM product_card_features WHERE product_id = ?',
                            [$id],
                        );
                        if ((int) ($sizes['total'] ?? 0) >= 6) {
                            throw new InvalidArgumentException(trans('products.features_count'));
                        }
                        $featureId = (int) $connection->table('product_card_features')->insert([
                            'product_id' => $id,
                            'preset_id' => $presetId,
                            'icon_key' => $icon,
                            'sort_order' => (int) ($sizes['max_sort'] ?? 0) + 1,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }

                    foreach ($this->locales() as $loc) {
                        $label = (string) ($preset['translations'][$loc]['label'] ?? $preset['translations'][$fallback]['label'] ?? '');
                        $existing = $connection->selectOne(
                            'SELECT id, feature_value FROM product_card_feature_translations WHERE feature_id = ? AND locale = ? LIMIT 1',
                            [$featureId, $loc],
                        );
                        $rowValue = $loc === $locale ? $text : (string) ($existing['feature_value'] ?? '');
                        if ($label === '' && $rowValue === '') {
                            if ($existing !== null) {
                                $connection->affect('DELETE FROM product_card_feature_translations WHERE id = ?', [(int) $existing['id']]);
                            }
                            continue;
                        }
                        if ($existing !== null) {
                            $connection->table('product_card_feature_translations')->where('id', (int) $existing['id'])
                                ->update(['label' => $label, 'feature_value' => $rowValue]);
                        } else {
                            $connection->table('product_card_feature_translations')->insert([
                                'feature_id' => $featureId,
                                'locale' => $loc,
                                'label' => $label,
                                'feature_value' => $rowValue,
                            ]);
                        }
                    }
                    return ['value' => $text, 'feature_id' => $featureId, 'preset_id' => $presetId];

                case 'spec_value':
                    $specId = (int) ($payload['spec_id'] ?? 0);
                    $spec = $specId > 0 ? $connection->selectOne(
                        'SELECT id, spec_key FROM product_specifications WHERE id = ? AND product_id = ? AND variant_id IS NULL LIMIT 1',
                        [$specId, $id],
                    ) : null;
                    if ($spec === null) {
                        throw new InvalidArgumentException(trans('products.inline_invalid_value'));
                    }
                    $text = trim((string) $value);
                    if (mb_strlen($text) > 255) {
                        throw new InvalidArgumentException(trans('products.inline_too_long'));
                    }
                    if ($locale === $fallback) {
                        $connection->table('product_specifications')->where('id', $specId)->update([
                            'spec_value' => $text,
                            'updated_at' => $now,
                        ]);
                    }
                    $existing = $connection->selectOne(
                        'SELECT id, spec_key FROM product_specification_translations WHERE specification_id = ? AND locale = ? LIMIT 1',
                        [$specId, $locale],
                    );
                    if ($existing !== null) {
                        $connection->table('product_specification_translations')->where('id', (int) $existing['id'])->update(['spec_value' => $text]);
                    } else {
                        $connection->table('product_specification_translations')->insert([
                            'specification_id' => $specId,
                            'locale' => $locale,
                            'spec_key' => (string) $spec['spec_key'],
                            'spec_value' => $text,
                        ]);
                    }
                    $result = ['value' => $text];
                    if ((string) $spec['spec_key'] === 'Finish') {
                        $result['finishes'] = ProductCardPresenter::make($this->find($id)->translate($fallback), $fallback)['finishes'] ?? [];
                    }
                    return $result;

                case 'finish_set':
                    /* Card finish picker: canonical finishes are ticked from
                       the system catalogue — never typed by hand. The whole
                       Finish spec is written at once, in every locale. */
                    $catalog = [];
                    foreach (ProductCardPresenter::finishCatalog() as $option) {
                        $catalog[(string) $option['label']] = $option;
                    }
                    $chosen = [];
                    foreach (is_array($value) ? $value : [] as $label) {
                        $label = trim((string) $label);
                        if ($label !== '' && isset($catalog[$label]) && !in_array($label, $chosen, true)) {
                            $chosen[] = $label;
                        }
                    }
                    $english = implode(' / ', $chosen);
                    $spec = $connection->selectOne(
                        "SELECT id FROM product_specifications WHERE product_id = ? AND variant_id IS NULL AND spec_key = 'Finish' ORDER BY sort_order ASC, id ASC LIMIT 1",
                        [$id],
                    );

                    if ($english === '') {
                        if ($spec !== null) {
                            $connection->affect('DELETE FROM product_specification_translations WHERE specification_id = ?', [(int) $spec['id']]);
                            $connection->affect('DELETE FROM product_specifications WHERE id = ?', [(int) $spec['id']]);
                        }
                        return ['value' => '', 'spec_id' => 0];
                    }

                    if ($spec !== null) {
                        $specId = (int) $spec['id'];
                        $connection->table('product_specifications')->where('id', $specId)->update([
                            'spec_value' => $english,
                            'updated_at' => $now,
                        ]);
                    } else {
                        $maxSort = $connection->selectOne(
                            'SELECT COALESCE(MAX(sort_order), 0) AS max_sort FROM product_specifications WHERE product_id = ? AND variant_id IS NULL',
                            [$id],
                        );
                        $specId = (int) $connection->table('product_specifications')->insert([
                            'product_id' => $id,
                            'variant_id' => null,
                            'spec_key' => 'Finish',
                            'spec_value' => $english,
                            'unit' => null,
                            'sort_order' => (int) ($maxSort['max_sort'] ?? 0) + 1,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }

                    $finishKeys = ['en' => 'Finish', 'tr' => 'Yüzey Kaplaması', 'cs' => 'Povrchová úprava'];
                    foreach ($this->locales() as $loc) {
                        $localized = ProductCardPresenter::translateFinishValue($english, $loc) ?? $english;
                        $existing = $connection->selectOne(
                            'SELECT id FROM product_specification_translations WHERE specification_id = ? AND locale = ? LIMIT 1',
                            [$specId, $loc],
                        );
                        if ($existing !== null) {
                            $connection->table('product_specification_translations')->where('id', (int) $existing['id'])
                                ->update(['spec_value' => $localized]);
                        } else {
                            $connection->table('product_specification_translations')->insert([
                                'specification_id' => $specId,
                                'locale' => $loc,
                                'spec_key' => $finishKeys[$loc] ?? 'Finish',
                                'spec_value' => $localized,
                            ]);
                        }
                    }
                    return ['value' => $english, 'spec_id' => $specId];

                default:
                    throw new InvalidArgumentException(trans('products.inline_unknown_field'));
            }
        });

        $this->activity->updated('product', $id);
        Product::clearStaticCaches();

        return $result;
    }

    /** @return array{is_featured: int, sort_order: int} */
    public function toggleFeatured(int $id): array
    {
        $product = $this->find($id);
        $featured = (int) ($product->is_featured ?? 0) === 1 ? 0 : 1;

        $result = $this->inlineUpdate($id, ['field' => 'is_featured', 'value' => $featured]);

        return [
            'is_featured' => (int) $result['value'],
            'sort_order' => (int) ($result['sort_order'] ?? 0),
        ];
    }

    /**
     * Admin product picker search: every status, seat covers included.
     *
     * @return array<int, array{id: int, model_code: string, slug: string, status: string, name: string}>
     */
    /* ------------------------------------------------------- admin card grid */

    /**
     * One page of the admin card grid. Colour families collapse to a single
     * card before pagination (exactly like the storefront), so a family can
     * never split across pages; the first matching member represents it.
     *
     * @param array{search?: string, category_id?: int, status?: string, featured?: bool, is_new?: bool, incomplete?: bool, sort?: string} $filters
     * @return array{ids: int[], total: int, pages: int, page: int, families: array<int, int[]>}
     */
    public function adminGrid(array $filters, int $page = 1, int $perPage = 24): array
    {
        $connection = Product::query()->connection();
        $locale = (string) config('localization.fallback', 'en');

        $where = ['p.deleted_at IS NULL'];
        $bindings = [];

        $status = (string) ($filters['status'] ?? '');
        if ($status === 'not_live') {
            /* "Hidden from store": exactly what the card visibility badge
               judges — non-public status OR storefront media incomplete. */
            $publicPlaceholders = implode(',', array_fill(0, count(ProductVisibility::PUBLIC_STATUSES), '?'));
            $where[] = "(p.status NOT IN ({$publicPlaceholders}) OR NOT (" . ProductVisibility::mediaReadySql('p') . '))';
            $bindings = [...$bindings, ...ProductVisibility::PUBLIC_STATUSES];
        } elseif ($status !== '' && in_array($status, self::STATUSES, true)) {
            $where[] = 'p.status = ?';
            $bindings[] = $status;
        }
        if (!empty($filters['featured'])) {
            $where[] = 'p.is_featured = 1';
        }
        if (!empty($filters['is_new']) && $this->hasIsNewColumn()) {
            $where[] = 'p.is_new = 1';
        }

        $categoryId = (int) ($filters['category_id'] ?? 0);
        if ($categoryId > 0) {
            $categoryIds = app(\App\Services\CategoryService::class)->descendantIds($categoryId);
            if ($categoryIds === []) {
                $where[] = '1 = 0';
            } else {
                $where[] = 'p.category_id IN (' . implode(',', array_fill(0, count($categoryIds), '?')) . ')';
                $bindings = [...$bindings, ...$categoryIds];
            }
        }

        $join = ' INNER JOIN product_translations pt ON pt.product_id = p.id AND pt.locale = ?';
        $joinBindings = [$locale];

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            /* code matches by prefix ("codes starting with…"), names by contains */
            $where[] = '(p.model_code LIKE ? OR pt.name LIKE ? OR p.slug LIKE ?)';
            $bindings[] = $search . '%';
            $bindings[] = '%' . $search . '%';
            $bindings[] = '%' . $search . '%';
        }

        if (!empty($filters['incomplete'])) {
            $where[] = '('
                . 'NOT (' . ProductVisibility::mediaReadySql('p') . ')'
                . " OR TRIM(COALESCE(pt.name, '')) = ''"
                . ' OR p.category_id IS NULL'
                . ' OR NOT EXISTS (SELECT 1 FROM product_variants v WHERE v.product_id = p.id AND v.deleted_at IS NULL AND v.price > 0)'
                . ' OR (SELECT COUNT(*) FROM product_card_features f WHERE f.product_id = p.id) < 4'
                . " OR NOT EXISTS (SELECT 1 FROM product_specifications s WHERE s.product_id = p.id AND s.variant_id IS NULL AND LOWER(s.spec_key) = 'finish')"
                . ')';
        }

        $sort = (string) ($filters['sort'] ?? 'model');
        $nameCollate = $connection->driver() === 'sqlite' ? 'pt.name COLLATE NOCASE ASC' : 'pt.name ASC';
        $orderBy = match ($sort) {
            'name' => "p.is_featured DESC, p.sort_order ASC, {$nameCollate}, p.id DESC",
            'newest' => 'p.id DESC',
            'curated' => 'p.is_featured DESC, p.sort_order ASC, p.id DESC',
            default => 'p.is_featured DESC, p.sort_order ASC, p.model_code ASC, p.id DESC',
        };

        $rows = $connection->select(
            "SELECT p.id FROM products p{$join} WHERE " . implode(' AND ', $where) . " ORDER BY {$orderBy}",
            [...$joinBindings, ...$bindings],
        );

        $familyMap = app(ProductColorFamilyService::class)->familyMap();
        $representatives = [];
        $families = [];
        $seen = [];
        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $family = $familyMap[$id] ?? null;
            $key = $family !== null ? 'family-' . $family[0] : 'product-' . $id;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $representatives[] = $id;
            if ($family !== null && count($family) >= 2) {
                $families[$id] = $family;
            }
        }

        $total = count($representatives);
        $pages = max(1, (int) ceil($total / max(1, $perPage)));
        $page = min(max(1, $page), $pages);
        $pageIds = array_slice($representatives, ($page - 1) * $perPage, $perPage);

        return [
            'ids' => array_values($pageIds),
            'total' => $total,
            'pages' => $pages,
            'page' => $page,
            'families' => array_intersect_key($families, array_flip($pageIds)),
        ];
    }

    /**
     * What each product is still missing before it can shine on the site.
     * Mirrors the `incomplete` SQL filter of adminGrid().
     *
     * @param int[] $productIds
     * @return array<int, string[]> product id => reason keys (photo, drawing, name, category, price, features, finish)
     */
    public function incompleteReasons(array $productIds): array
    {
        $ids = array_values(array_unique(array_map('intval', $productIds)));
        if ($ids === []) {
            return [];
        }

        $connection = Product::query()->connection();
        $in = '(' . implode(',', array_fill(0, count($ids), '?')) . ')';
        $reasons = array_fill_keys($ids, []);

        $hasPhoto = [];
        $hasDrawing = [];
        foreach ($connection->select(
            "SELECT pm.product_id, pm.type FROM product_media pm
             INNER JOIN media m ON m.id = pm.media_id AND m.status = 'active' AND m.deleted_at IS NULL
             WHERE pm.product_id IN {$in} AND pm.variant_id IS NULL",
            $ids,
        ) as $row) {
            $pid = (int) $row['product_id'];
            if ((string) $row['type'] === 'drawing') {
                $hasDrawing[$pid] = true;
            } elseif ((string) $row['type'] !== 'situ') {
                $hasPhoto[$pid] = true;
            }
        }

        $named = [];
        foreach ($connection->select(
            "SELECT product_id FROM product_translations WHERE locale = 'en' AND TRIM(COALESCE(name, '')) <> '' AND product_id IN {$in}",
            $ids,
        ) as $row) {
            $named[(int) $row['product_id']] = true;
        }

        $categorised = [];
        foreach ($connection->select("SELECT id FROM products WHERE category_id IS NOT NULL AND id IN {$in}", $ids) as $row) {
            $categorised[(int) $row['id']] = true;
        }

        $priced = [];
        foreach ($connection->select(
            "SELECT DISTINCT product_id FROM product_variants WHERE deleted_at IS NULL AND price > 0 AND product_id IN {$in}",
            $ids,
        ) as $row) {
            $priced[(int) $row['product_id']] = true;
        }

        $featureCounts = [];
        foreach ($connection->select(
            "SELECT product_id, COUNT(*) AS total FROM product_card_features WHERE product_id IN {$in} GROUP BY product_id",
            $ids,
        ) as $row) {
            $featureCounts[(int) $row['product_id']] = (int) $row['total'];
        }

        $finished = [];
        foreach ($connection->select(
            "SELECT DISTINCT product_id FROM product_specifications
             WHERE variant_id IS NULL AND LOWER(spec_key) = 'finish' AND product_id IN {$in}",
            $ids,
        ) as $row) {
            $finished[(int) $row['product_id']] = true;
        }

        foreach ($ids as $id) {
            if (!isset($hasPhoto[$id])) {
                $reasons[$id][] = 'photo';
            }
            if (!isset($hasDrawing[$id])) {
                $reasons[$id][] = 'drawing';
            }
            if (!isset($named[$id])) {
                $reasons[$id][] = 'name';
            }
            if (!isset($categorised[$id])) {
                $reasons[$id][] = 'category';
            }
            if (!isset($priced[$id])) {
                $reasons[$id][] = 'price';
            }
            if (($featureCounts[$id] ?? 0) < 4) {
                $reasons[$id][] = 'features';
            }
            if (!isset($finished[$id])) {
                $reasons[$id][] = 'finish';
            }
        }

        return $reasons;
    }

    /** Total number of products missing at least one thing (dashboard + sidebar badge). */
    public function incompleteCount(): int
    {
        $connection = Product::query()->connection();
        $row = $connection->selectOne(
            "SELECT COUNT(*) AS total FROM products p
             INNER JOIN product_translations pt ON pt.product_id = p.id AND pt.locale = 'en'
             WHERE p.deleted_at IS NULL AND ("
            . 'NOT (' . ProductVisibility::mediaReadySql('p') . ')'
            . " OR TRIM(COALESCE(pt.name, '')) = ''"
            . ' OR p.category_id IS NULL'
            . ' OR NOT EXISTS (SELECT 1 FROM product_variants v WHERE v.product_id = p.id AND v.deleted_at IS NULL AND v.price > 0)'
            . ' OR (SELECT COUNT(*) FROM product_card_features f WHERE f.product_id = p.id) < 4'
            . " OR NOT EXISTS (SELECT 1 FROM product_specifications s WHERE s.product_id = p.id AND s.variant_id IS NULL AND LOWER(s.spec_key) = 'finish')"
            . ')',
        );

        return (int) ($row['total'] ?? 0);
    }

    /**
     * Per-reason tallies across the whole catalogue (incomplete page header).
     *
     * @return array<string, int> reason key => product count
     */
    public function incompleteSummary(): array
    {
        $rows = Product::query()->connection()->select(
            'SELECT id FROM products WHERE deleted_at IS NULL',
        );
        $ids = array_map(static fn (array $row): int => (int) $row['id'], $rows);

        $summary = [];
        foreach ($this->incompleteReasons($ids) as $reasons) {
            foreach ($reasons as $reason) {
                $summary[$reason] = ($summary[$reason] ?? 0) + 1;
            }
        }

        return $summary;
    }

    /**
     * Save one editor section of the expanded product card. Every section
     * reuses the same sync invariants as the old full-form update.
     *
     * @param array<string, mixed> $payload
     */
    public function syncSection(int $id, string $section, array $payload): void
    {
        $product = $this->find($id);
        $modelCode = (string) ($product->model_code ?? '');
        $connection = Product::query()->connection();

        $connection->transaction(function () use ($connection, $id, $section, $payload, $modelCode): void {
            switch ($section) {
                case 'classification':
                    $nullableId = static function (mixed $value): ?int {
                        $int = (int) ($value ?? 0);
                        return $int > 0 ? $int : null;
                    };
                    $connection->table('products')->where('id', $id)->update([
                        'collection_id' => $nullableId($payload['collection_id'] ?? null),
                        'brand_id' => $nullableId($payload['brand_id'] ?? null),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    break;

                case 'texts':
                    $translations = is_array($payload['translations'] ?? null) ? $payload['translations'] : [];
                    $this->localization->syncTranslations('product', $id, $translations);
                    $this->refreshArtifacts($id);
                    break;

                case 'variants':
                    $this->syncVariants($id, is_array($payload['variants'] ?? null) ? $payload['variants'] : [], $modelCode, null);
                    break;

                case 'specs':
                    $this->syncSpecifications($id, is_array($payload['specs'] ?? null) ? $payload['specs'] : []);
                    break;

                case 'features':
                    $this->syncCardFeatures($id, is_array($payload['features'] ?? null) ? $payload['features'] : []);
                    break;

                case 'dimensions':
                    $this->syncDimensions($id, is_array($payload['dimensions'] ?? null) ? $payload['dimensions'] : null);
                    break;

                case 'relations':
                    $this->syncRelations($id, is_array($payload['relations'] ?? null) ? $payload['relations'] : []);
                    $this->refreshArtifacts($id);
                    break;

                case 'seo':
                    $this->syncSeoAll($id, is_array($payload['seo'] ?? null) ? $payload['seo'] : []);
                    break;

                default:
                    throw new InvalidArgumentException('Unknown editor section.');
            }
        });

        Product::clearStaticCaches();
        $this->activity->updated('product', $id, ['section' => $section]);
    }

    /**
     * Create a draft product straight from the card grid. It appears as a new
     * card with a placeholder code that is edited in place like everything else.
     */
    public function createDraft(?int $categoryId = null): Product
    {
        if ($categoryId !== null && $categoryId > 0 && !$this->isActiveLeaf($categoryId)) {
            throw new InvalidArgumentException(trans('products.inline_category_leaf'));
        }

        $connection = Product::query()->connection();
        do {
            $code = 'NEW-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
            $taken = $connection->selectOne(
                'SELECT id FROM products WHERE model_code = ? AND deleted_at IS NULL LIMIT 1',
                [$code],
            );
        } while ($taken !== null);

        return $this->create(
            [
                'model_code' => $code,
                'slug' => '',
                'category_id' => $categoryId !== null && $categoryId > 0 ? $categoryId : null,
                'collection_id' => null,
                'brand_id' => null,
                'status' => 'draft',
                'is_featured' => '0',
                'sort_order' => 0,
            ],
            ['en' => ['name' => 'New product', 'short_description' => '', 'description' => '']],
            [],
        );
    }

    private function hasIsNewColumn(): bool
    {
        static $has = null;
        if ($has !== null) {
            return $has;
        }

        $connection = Product::query()->connection();
        if ($connection->driver() === 'sqlite') {
            $has = false;
            foreach ($connection->select('PRAGMA table_info(products)') as $row) {
                if ((string) ($row['name'] ?? '') === 'is_new') {
                    $has = true;
                    break;
                }
            }

            return $has;
        }

        $row = $connection->selectOne(
            'SELECT COUNT(*) AS total FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            ['products', 'is_new'],
        );

        return $has = (int) ($row['total'] ?? 0) > 0;
    }

    public function adminSearch(string $query, int $excludeId = 0, int $limit = 20): array
    {
        $term = '%' . trim($query) . '%';
        $rows = Product::query()->connection()->select(
            "SELECT p.id, p.model_code, p.slug, p.status, COALESCE(pt.name, '') AS name
             FROM products p
             LEFT JOIN product_translations pt ON pt.product_id = p.id AND pt.locale = 'en'
             WHERE p.deleted_at IS NULL AND p.id != ?
               AND (p.model_code LIKE ? OR pt.name LIKE ? OR p.slug LIKE ?)
             ORDER BY p.model_code ASC, p.id ASC
             LIMIT ?",
            [$excludeId, $term, $term, $term, max(1, $limit)],
        );

        return array_map(static fn (array $row): array => [
            'id' => (int) $row['id'],
            'model_code' => (string) ($row['model_code'] ?? ''),
            'slug' => (string) $row['slug'],
            'status' => (string) $row['status'],
            'name' => (string) $row['name'],
        ], $rows);
    }

    /* ------------------------------------------------------ editor datasets */

    /**
     * Current relation state for the editor: colour family (connected
     * component), linked seat covers, parent WCs and the standalone flag.
     *
     * @return array{color_family: int[], seat_covers: int[], parent_wcs: int[], seat_cover_only: bool}
     */
    public function relationState(int $id): array
    {
        $connection = Product::query()->connection();

        $adjacency = [];
        foreach ($connection->select(
            "SELECT pr.product_id, pr.related_product_id FROM product_relations pr
             INNER JOIN products p ON p.id = pr.product_id AND p.deleted_at IS NULL
             INNER JOIN products rp ON rp.id = pr.related_product_id AND rp.deleted_at IS NULL
             WHERE pr.relation_type = 'color_variant'",
        ) as $row) {
            $a = (int) $row['product_id'];
            $b = (int) $row['related_product_id'];
            if ($a !== $b) {
                $adjacency[$a][$b] = true;
                $adjacency[$b][$a] = true;
            }
        }
        $family = [];
        $stack = [$id];
        $seen = [$id => true];
        while ($stack !== []) {
            $currentId = array_pop($stack);
            foreach (array_keys($adjacency[$currentId] ?? []) as $neighbor) {
                if (!isset($seen[$neighbor])) {
                    $seen[$neighbor] = true;
                    $family[] = $neighbor;
                    $stack[] = $neighbor;
                }
            }
        }
        sort($family);

        $seatCovers = array_map(
            static fn (array $row): int => (int) $row['related_product_id'],
            $connection->select(
                "SELECT related_product_id FROM product_relations WHERE product_id = ? AND relation_type = 'seat_cover' ORDER BY id ASC",
                [$id],
            ),
        );
        $parentWcs = array_map(
            static fn (array $row): int => (int) $row['product_id'],
            $connection->select(
                "SELECT product_id FROM product_relations WHERE related_product_id = ? AND relation_type = 'seat_cover' ORDER BY id ASC",
                [$id],
            ),
        );
        $only = $connection->selectOne(
            "SELECT id FROM product_relations WHERE product_id = ? AND related_product_id = ? AND relation_type = 'seat_cover_only' LIMIT 1",
            [$id, $id],
        );

        return [
            'color_family' => $family,
            'seat_covers' => $seatCovers,
            'parent_wcs' => $parentWcs,
            'seat_cover_only' => $only !== null,
        ];
    }

    /**
     * Picker labels for a set of product ids, keyed by id.
     *
     * @param int[] $ids
     * @return array<int, array{id: int, model_code: string, name: string}>
     */
    public function pickerLabels(array $ids): array
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if ($ids === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $out = [];
        foreach (Product::query()->connection()->select(
            "SELECT p.id, p.model_code, COALESCE(pt.name, '') AS name
             FROM products p
             LEFT JOIN product_translations pt ON pt.product_id = p.id AND pt.locale = 'en'
             WHERE p.id IN ({$placeholders})",
            $ids,
        ) as $row) {
            $out[(int) $row['id']] = [
                'id' => (int) $row['id'],
                'model_code' => (string) ($row['model_code'] ?? ''),
                'name' => (string) $row['name'],
            ];
        }

        return $out;
    }

    /**
     * Raw card-feature rows with every locale translation (editor form).
     *
     * @return array<int, array<string, mixed>>
     */
    public function cardFeaturesRaw(int $id): array
    {
        $connection = Product::query()->connection();
        $rows = $connection->select(
            'SELECT id, preset_id, icon_key, sort_order FROM product_card_features WHERE product_id = ? ORDER BY sort_order ASC, id ASC',
            [$id],
        );
        $translations = [];
        if ($rows !== []) {
            $featureIds = array_map(static fn (array $row): int => (int) $row['id'], $rows);
            $placeholders = implode(',', array_fill(0, count($featureIds), '?'));
            foreach ($connection->select(
                "SELECT feature_id, locale, label, feature_value FROM product_card_feature_translations WHERE feature_id IN ({$placeholders})",
                $featureIds,
            ) as $tr) {
                $translations[(int) $tr['feature_id']][(string) $tr['locale']] = [
                    'label' => (string) $tr['label'],
                    'value' => (string) $tr['feature_value'],
                ];
            }
        }
        foreach ($rows as &$row) {
            $row['translations'] = $translations[(int) $row['id']] ?? [];
        }
        unset($row);

        return $rows;
    }

    /**
     * Active feature presets with translations (editor preset picker).
     *
     * @return array<int, array<string, mixed>>
     */
    public function featurePresets(): array
    {
        $connection = Product::query()->connection();
        $rows = $connection->select(
            'SELECT id, preset_key, icon_key, category_hint FROM product_feature_presets WHERE is_active = 1 ORDER BY sort_order ASC, id ASC',
        );
        $translations = [];
        if ($rows !== []) {
            $presetIds = array_map(static fn (array $row): int => (int) $row['id'], $rows);
            $placeholders = implode(',', array_fill(0, count($presetIds), '?'));
            foreach ($connection->select(
                "SELECT preset_id, locale, label, placeholder_value FROM product_feature_preset_translations WHERE preset_id IN ({$placeholders})",
                $presetIds,
            ) as $tr) {
                $translations[(int) $tr['preset_id']][(string) $tr['locale']] = [
                    'label' => (string) $tr['label'],
                    'placeholder' => (string) ($tr['placeholder_value'] ?? ''),
                ];
            }
        }
        foreach ($rows as &$row) {
            $row['translations'] = $translations[(int) $row['id']] ?? [];
        }
        unset($row);

        return $rows;
    }

    /** @return array<string, array<string, mixed>> locale => seo_meta row */
    public function seoRows(int $id): array
    {
        $out = [];
        foreach (Product::query()->connection()->select(
            'SELECT * FROM seo_meta WHERE product_id = ?',
            [$id],
        ) as $row) {
            $out[(string) $row['locale']] = $row;
        }

        return $out;
    }

    /**
     * Current datasheet document per language (first per language wins,
     * mirroring ProductRepository::datasheetUrl ordering).
     *
     * @return array<string, array{document_id: int, media_id: int, path: string, original_name: string}>
     */
    public function datasheets(int $id): array
    {
        $out = [];
        foreach (Product::query()->connection()->select(
            "SELECT pd.id AS document_id, pd.media_id, pd.language, m.path, m.original_name
             FROM product_documents pd
             INNER JOIN media m ON m.id = pd.media_id
             WHERE pd.product_id = ? AND pd.variant_id IS NULL
               AND pd.document_type IN ('datasheet', 'data_sheet', 'technical_sheet')
             ORDER BY pd.sort_order ASC, pd.id ASC",
            [$id],
        ) as $row) {
            $language = (string) $row['language'];
            $out[$language] ??= [
                'document_id' => (int) $row['document_id'],
                'media_id' => (int) $row['media_id'],
                'path' => (string) $row['path'],
                'original_name' => (string) ($row['original_name'] ?? ''),
            ];
        }

        return $out;
    }

    /**
     * Resolved card-feature chips (with feature ids for inline editing) for a
     * page of admin product cards, in one set of bulk queries.
     *
     * @param int[] $productIds
     * @return array<int, array<int, array{id: int, icon: string, label: string, value: string}>>
     */
    public function cardChipsBulk(array $productIds, string $locale = 'en'): array
    {
        $productIds = array_values(array_filter(array_map('intval', $productIds)));
        if ($productIds === []) {
            return [];
        }
        $connection = Product::query()->connection();
        $fallback = (string) config('localization.fallback', 'en');
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));

        $presets = [];
        foreach ($this->featurePresets() as $preset) {
            $presets[(int) $preset['id']] = $preset;
        }

        $features = $connection->select(
            "SELECT id, product_id, preset_id, icon_key, sort_order FROM product_card_features
             WHERE product_id IN ({$placeholders}) ORDER BY sort_order ASC, id ASC",
            $productIds,
        );
        $featureIds = array_map(static fn (array $row): int => (int) $row['id'], $features);
        $translations = [];
        if ($featureIds !== []) {
            $fPlaceholders = implode(',', array_fill(0, count($featureIds), '?'));
            foreach ($connection->select(
                "SELECT feature_id, locale, label, feature_value FROM product_card_feature_translations WHERE feature_id IN ({$fPlaceholders})",
                $featureIds,
            ) as $tr) {
                $translations[(int) $tr['feature_id']][(string) $tr['locale']] = [
                    'label' => (string) $tr['label'],
                    'value' => (string) $tr['feature_value'],
                ];
            }
        }

        $out = [];
        foreach ($features as $feature) {
            $preset = $presets[(int) ($feature['preset_id'] ?? 0)] ?? null;
            $trMap = $translations[(int) $feature['id']] ?? [];
            $chosen = $trMap[$locale] ?? $trMap[$fallback] ?? (reset($trMap) ?: []);
            $label = trim((string) ($chosen['label'] ?? ''));
            if ($label === '' && $preset !== null) {
                $label = trim((string) ($preset['translations'][$locale]['label'] ?? $preset['translations'][$fallback]['label'] ?? ''));
            }
            $value = trim((string) ($chosen['value'] ?? ''));
            if ($value === '' && $preset !== null) {
                $value = trim((string) ($preset['translations'][$locale]['placeholder'] ?? $preset['translations'][$fallback]['placeholder'] ?? ''));
            }
            if ($label === '' || $value === '') {
                continue;
            }
            $icon = trim((string) ($feature['icon_key'] ?? ''));
            if ($icon === '' && $preset !== null) {
                $icon = (string) $preset['icon_key'];
            }
            $out[(int) $feature['product_id']][] = [
                'id' => (int) $feature['id'],
                'preset_id' => (int) ($feature['preset_id'] ?? 0),
                'icon' => ProductFeatureIconCatalog::normalize($icon),
                'label' => $label,
                'value' => $value,
                /* value before the preset-placeholder backfill, for editing */
                'value_raw' => trim((string) ($chosen['value'] ?? '')),
            ];
        }

        return $out;
    }

    /**
     * The canonical Finish specification per product (inline editing target).
     *
     * @param int[] $productIds
     * @return array<int, array{id: int, value: string}>
     */
    public function finishSpecsBulk(array $productIds): array
    {
        $productIds = array_values(array_filter(array_map('intval', $productIds)));
        if ($productIds === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $out = [];
        foreach (Product::query()->connection()->select(
            "SELECT id, product_id, spec_value FROM product_specifications
             WHERE variant_id IS NULL AND spec_key = 'Finish' AND product_id IN ({$placeholders})
             ORDER BY sort_order ASC, id ASC",
            $productIds,
        ) as $row) {
            $out[(int) $row['product_id']] ??= [
                'id' => (int) $row['id'],
                'value' => (string) $row['spec_value'],
            ];
        }

        return $out;
    }

    /**
     * Replace the datasheet for one language.
     *
     * @return int[] media ids detached by the replacement (for file cleanup)
     */
    public function attachDatasheet(int $id, string $locale, int $mediaId): array
    {
        $connection = Product::query()->connection();
        $detached = $this->detachDatasheetRows($id, $locale);
        $now = date('Y-m-d H:i:s');
        $connection->table('product_documents')->insert([
            'product_id' => $id,
            'variant_id' => null,
            'media_id' => $mediaId,
            'document_type' => 'datasheet',
            'language' => $locale,
            'sort_order' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->activity->updated('product', $id);

        return $detached;
    }

    /** @return int[] media ids detached (for file cleanup) */
    public function detachDatasheet(int $id, string $locale): array
    {
        $detached = $this->detachDatasheetRows($id, $locale);
        if ($detached !== []) {
            $this->activity->updated('product', $id);
        }

        return $detached;
    }

    /** @return int[] */
    private function detachDatasheetRows(int $id, string $locale): array
    {
        $connection = Product::query()->connection();
        $mediaIds = array_map(
            static fn (array $row): int => (int) $row['media_id'],
            $connection->select(
                "SELECT media_id FROM product_documents
                 WHERE product_id = ? AND language = ? AND variant_id IS NULL
                   AND document_type IN ('datasheet', 'data_sheet', 'technical_sheet')",
                [$id, $locale],
            ),
        );
        if ($mediaIds !== []) {
            $connection->affect(
                "DELETE FROM product_documents
                 WHERE product_id = ? AND language = ? AND variant_id IS NULL
                   AND document_type IN ('datasheet', 'data_sheet', 'technical_sheet')",
                [$id, $locale],
            );
        }

        return $mediaIds;
    }

    /* ------------------------------------------------------------ extra sync */

    /**
     * @param array<string, mixed> $data core data (price fallback)
     * @param array<string, mixed> $extra
     */
    private function syncExtra(int $productId, string $modelCode, array $data, array $extra): void
    {
        if (array_key_exists('variants', $extra)) {
            $this->syncVariants(
                $productId,
                is_array($extra['variants']) ? $extra['variants'] : [],
                $modelCode,
                isset($data['price']) && $data['price'] !== null ? (float) $data['price'] : null,
            );
        } else {
            $this->syncDefaultVariant(
                $productId,
                $modelCode,
                isset($data['price']) && $data['price'] !== null ? (float) $data['price'] : null,
            );
        }

        if (array_key_exists('specs', $extra)) {
            $this->syncSpecifications($productId, is_array($extra['specs']) ? $extra['specs'] : []);
        }
        if (array_key_exists('features', $extra)) {
            $this->syncCardFeatures($productId, is_array($extra['features']) ? $extra['features'] : []);
        }
        if (array_key_exists('dimensions', $extra)) {
            $this->syncDimensions($productId, is_array($extra['dimensions']) ? $extra['dimensions'] : null);
        }
        if (array_key_exists('seo', $extra)) {
            $this->syncSeoAll($productId, is_array($extra['seo']) ? $extra['seo'] : []);
        }
        if (array_key_exists('relations', $extra)) {
            $this->syncRelations($productId, is_array($extra['relations']) ? $extra['relations'] : []);
        }
    }

    /**
     * Replace the product's sellable variants. Rows carry their id when they
     * already exist; anything missing from the payload is soft-deleted.
     *
     * @param array<int, array<string, mixed>> $rows
     */
    private function syncVariants(int $productId, array $rows, string $modelCode, ?float $fallbackPrice = null): void
    {
        $connection = Product::query()->connection();
        $now = date('Y-m-d H:i:s');

        $normalized = [];
        $seenSkus = [];
        foreach (array_values($rows) as $index => $row) {
            if (!is_array($row)) {
                continue;
            }
            $sku = trim((string) ($row['sku'] ?? ''));
            if ($sku === '') {
                $sku = $modelCode !== '' ? $modelCode : 'LUFLY-' . $productId;
                if ($index > 0) {
                    $sku .= '-' . ($index + 1);
                }
            }
            if (isset($seenSkus[$sku])) {
                throw new InvalidArgumentException(trans('products.variant_sku_taken', ['sku' => $sku]));
            }
            $seenSkus[$sku] = true;
            $stock = (string) ($row['stock_status'] ?? 'in_stock');
            $normalized[] = [
                'id' => (int) ($row['id'] ?? 0),
                'sku' => mb_substr($sku, 0, 100),
                'variant_name' => $this->nullableTrimmed($row['variant_name'] ?? null, 150),
                'price' => is_numeric($row['price'] ?? null) ? max(0.0, (float) $row['price']) : 0.0,
                'stock_status' => in_array($stock, self::STOCK_STATUSES, true) ? $stock : 'in_stock',
                'status' => in_array((string) ($row['status'] ?? 'active'), ['active', 'inactive'], true) ? (string) $row['status'] : 'active',
                'sort_order' => is_numeric($row['sort_order'] ?? null) ? (int) $row['sort_order'] : $index + 1,
            ];
        }

        if ($normalized === []) {
            $this->syncDefaultVariant($productId, $modelCode, $fallbackPrice);
            return;
        }

        $keptIds = [];
        foreach ($normalized as $row) {
            $existing = $connection->selectOne(
                'SELECT id, product_id, deleted_at FROM product_variants WHERE sku = ? LIMIT 1',
                [$row['sku']],
            );
            if ($existing !== null) {
                $ownerMatches = $row['id'] > 0
                    ? (int) $existing['id'] === $row['id']
                    : ((int) $existing['product_id'] === $productId && $existing['deleted_at'] !== null);
                if (!$ownerMatches) {
                    throw new InvalidArgumentException(trans('products.variant_sku_taken', ['sku' => $row['sku']]));
                }
                $row['id'] = (int) $existing['id'];
            }

            $fields = [
                'product_id' => $productId,
                'sku' => $row['sku'],
                'variant_name' => $row['variant_name'],
                'price' => $row['price'],
                'stock_status' => $row['stock_status'],
                'sort_order' => $row['sort_order'],
                'status' => $row['status'],
                'deleted_at' => null,
                'updated_at' => $now,
            ];
            if ($row['id'] > 0) {
                $belongs = $connection->selectOne(
                    'SELECT id FROM product_variants WHERE id = ? AND product_id = ? LIMIT 1',
                    [$row['id'], $productId],
                );
                if ($belongs === null) {
                    throw new InvalidArgumentException(trans('products.variant_sku_taken', ['sku' => $row['sku']]));
                }
                $connection->table('product_variants')->where('id', $row['id'])->update($fields);
                $keptIds[] = $row['id'];
            } else {
                $keptIds[] = (int) $connection->table('product_variants')->insert($fields + ['created_at' => $now]);
            }
        }

        if ($keptIds !== []) {
            $placeholders = implode(',', array_fill(0, count($keptIds), '?'));
            $connection->affect(
                "UPDATE product_variants SET deleted_at = ?, updated_at = ?
                 WHERE product_id = ? AND deleted_at IS NULL AND id NOT IN ({$placeholders})",
                [$now, $now, $productId, ...$keptIds],
            );
        }
    }

    /**
     * Replace product-level specifications (variant_id IS NULL). The English
     * key/value double as the canonical columns; every locale row round-trips
     * through product_specification_translations.
     *
     * @param array<int, array<string, mixed>> $rows ['id','unit','key'=>[locale=>..],'value'=>[locale=>..]]
     */
    private function syncSpecifications(int $productId, array $rows): void
    {
        $connection = Product::query()->connection();
        $now = date('Y-m-d H:i:s');
        $locales = $this->locales();
        $fallback = (string) config('localization.fallback', 'en');

        $keptIds = [];
        $order = 0;
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $keys = $this->localeMap($row['key'] ?? [], $locales, 150);
            $values = $this->localeMap($row['value'] ?? [], $locales, 255);
            $canonicalKey = $this->firstNonEmpty($keys, $fallback);
            $canonicalValue = $this->firstNonEmpty($values, $fallback);
            if ($canonicalKey === '' && $canonicalValue === '') {
                continue;
            }

            /* Finish: derive missing locale values from the canonical
               dictionary so swatches stay consistent across languages. */
            if ($canonicalKey === 'Finish' && $canonicalValue !== '') {
                foreach ($locales as $locale) {
                    if (($values[$locale] ?? '') === '') {
                        $derived = ProductCardPresenter::translateFinishValue($canonicalValue, $locale);
                        if ($derived !== null) {
                            $values[$locale] = $derived;
                        }
                    }
                }
            }

            $order++;
            $fields = [
                'product_id' => $productId,
                'variant_id' => null,
                'spec_key' => $canonicalKey,
                'spec_value' => $canonicalValue,
                'unit' => $this->nullableTrimmed($row['unit'] ?? null, 50),
                'sort_order' => $order,
                'updated_at' => $now,
            ];
            $specId = (int) ($row['id'] ?? 0);
            if ($specId > 0) {
                $belongs = $connection->selectOne(
                    'SELECT id FROM product_specifications WHERE id = ? AND product_id = ? AND variant_id IS NULL LIMIT 1',
                    [$specId, $productId],
                );
                if ($belongs === null) {
                    $specId = 0;
                }
            }
            if ($specId > 0) {
                $connection->table('product_specifications')->where('id', $specId)->update($fields);
            } else {
                $specId = (int) $connection->table('product_specifications')->insert($fields + ['created_at' => $now]);
            }
            $keptIds[] = $specId;

            foreach ($locales as $locale) {
                $key = $keys[$locale] ?? '';
                $value = $values[$locale] ?? '';
                $existing = $connection->selectOne(
                    'SELECT id FROM product_specification_translations WHERE specification_id = ? AND locale = ? LIMIT 1',
                    [$specId, $locale],
                );
                if ($key === '' && $value === '') {
                    if ($existing !== null) {
                        $connection->affect('DELETE FROM product_specification_translations WHERE id = ?', [(int) $existing['id']]);
                    }
                    continue;
                }
                $translation = [
                    'spec_key' => $key !== '' ? $key : $canonicalKey,
                    'spec_value' => $value !== '' ? $value : $canonicalValue,
                ];
                if ($existing !== null) {
                    $connection->table('product_specification_translations')->where('id', (int) $existing['id'])->update($translation);
                } else {
                    $connection->table('product_specification_translations')->insert($translation + [
                        'specification_id' => $specId,
                        'locale' => $locale,
                    ]);
                }
            }
        }

        /* Anything not resubmitted is removed with its translations. */
        $existingIds = array_map(
            static fn (array $row): int => (int) $row['id'],
            $connection->select(
                'SELECT id FROM product_specifications WHERE product_id = ? AND variant_id IS NULL',
                [$productId],
            ),
        );
        $removeIds = array_values(array_diff($existingIds, $keptIds));
        if ($removeIds !== []) {
            $placeholders = implode(',', array_fill(0, count($removeIds), '?'));
            $connection->affect("DELETE FROM product_specification_translations WHERE specification_id IN ({$placeholders})", $removeIds);
            $connection->affect("DELETE FROM product_specifications WHERE id IN ({$placeholders})", $removeIds);
        }
    }

    /**
     * Replace the 0-or-4-to-6 card features. Preset labels fill any locale
     * label left blank, exactly as the storefront would resolve them.
     *
     * @param array<int, array<string, mixed>> $rows ['id','preset_id','icon_key','label'=>[locale=>..],'value'=>[locale=>..]]
     */
    private function syncCardFeatures(int $productId, array $rows): void
    {
        $connection = Product::query()->connection();
        $now = date('Y-m-d H:i:s');
        $locales = $this->locales();
        $fallback = (string) config('localization.fallback', 'en');

        $normalized = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $labels = $this->localeMap($row['label'] ?? [], $locales, 120);
            $values = $this->localeMap($row['value'] ?? [], $locales, 255);
            $presetId = (int) ($row['preset_id'] ?? 0);
            if ($presetId === 0 && $this->firstNonEmpty($labels, $fallback) === '' && $this->firstNonEmpty($values, $fallback) === '') {
                continue;
            }
            $normalized[] = [
                'id' => (int) ($row['id'] ?? 0),
                'preset_id' => $presetId,
                'icon_key' => trim((string) ($row['icon_key'] ?? '')),
                'label' => $labels,
                'value' => $values,
            ];
        }

        /* Any count from 0 to 6 may be saved while a product is being
           built up; below 4 it simply counts as incomplete and the
           storefront presenter backfills the card. */
        if (count($normalized) > 6) {
            throw new InvalidArgumentException(trans('products.features_count'));
        }

        $presets = [];
        foreach ($this->featurePresets() as $preset) {
            $presets[(int) $preset['id']] = $preset;
        }

        $keptIds = [];
        foreach (array_values($normalized) as $index => $row) {
            $preset = $presets[$row['preset_id']] ?? null;
            $icon = $row['icon_key'];
            if ($preset !== null) {
                /* A preset feature always carries the preset's own icon. */
                $icon = (string) $preset['icon_key'];
            }
            $icon = ProductFeatureIconCatalog::normalize($icon);
            if (!ProductFeatureIconCatalog::has($icon)) {
                throw new InvalidArgumentException(trans('products.features_icon_invalid'));
            }

            $fields = [
                'product_id' => $productId,
                'preset_id' => $preset !== null ? $row['preset_id'] : null,
                'icon_key' => $icon,
                'sort_order' => $index + 1,
                'updated_at' => $now,
            ];
            $featureId = $row['id'];
            if ($featureId > 0) {
                $belongs = $connection->selectOne(
                    'SELECT id FROM product_card_features WHERE id = ? AND product_id = ? LIMIT 1',
                    [$featureId, $productId],
                );
                if ($belongs === null) {
                    $featureId = 0;
                }
            }
            if ($featureId > 0) {
                $connection->table('product_card_features')->where('id', $featureId)->update($fields);
            } else {
                $featureId = (int) $connection->table('product_card_features')->insert($fields + ['created_at' => $now]);
            }
            $keptIds[] = $featureId;

            foreach ($locales as $locale) {
                $label = $row['label'][$locale] ?? '';
                if ($preset !== null) {
                    /* Preset labels are never typed by hand — they always
                       come from the preset's own translations. */
                    $label = (string) ($preset['translations'][$locale]['label'] ?? $preset['translations'][$fallback]['label'] ?? $label);
                }
                $value = $row['value'][$locale] ?? '';
                $existing = $connection->selectOne(
                    'SELECT id FROM product_card_feature_translations WHERE feature_id = ? AND locale = ? LIMIT 1',
                    [$featureId, $locale],
                );
                if ($label === '' && $value === '') {
                    if ($existing !== null) {
                        $connection->affect('DELETE FROM product_card_feature_translations WHERE id = ?', [(int) $existing['id']]);
                    }
                    continue;
                }
                $translation = ['label' => $label, 'feature_value' => $value];
                if ($existing !== null) {
                    $connection->table('product_card_feature_translations')->where('id', (int) $existing['id'])->update($translation);
                } else {
                    $connection->table('product_card_feature_translations')->insert($translation + [
                        'feature_id' => $featureId,
                        'locale' => $locale,
                    ]);
                }
            }
        }

        $existingIds = array_map(
            static fn (array $row): int => (int) $row['id'],
            $connection->select('SELECT id FROM product_card_features WHERE product_id = ?', [$productId]),
        );
        $removeIds = array_values(array_diff($existingIds, $keptIds));
        if ($removeIds !== []) {
            $placeholders = implode(',', array_fill(0, count($removeIds), '?'));
            $connection->affect("DELETE FROM product_card_feature_translations WHERE feature_id IN ({$placeholders})", $removeIds);
            $connection->affect("DELETE FROM product_card_features WHERE id IN ({$placeholders})", $removeIds);
        }
    }

    /** @param array<string, mixed>|null $dimensions */
    private function syncDimensions(int $productId, ?array $dimensions): void
    {
        $connection = Product::query()->connection();
        $now = date('Y-m-d H:i:s');
        $values = [
            'width_mm' => $this->nullableNumber($dimensions['width_mm'] ?? null),
            'height_mm' => $this->nullableNumber($dimensions['height_mm'] ?? null),
            'depth_mm' => $this->nullableNumber($dimensions['depth_mm'] ?? null),
            'weight_kg' => $this->nullableNumber($dimensions['weight_kg'] ?? null),
        ];

        $existing = $connection->selectOne(
            'SELECT id FROM product_dimensions WHERE product_id = ? AND variant_id IS NULL ORDER BY id ASC LIMIT 1',
            [$productId],
        );

        if (array_filter($values, static fn ($value): bool => $value !== null) === []) {
            if ($existing !== null) {
                $connection->affect('DELETE FROM product_dimensions WHERE id = ?', [(int) $existing['id']]);
            }
            return;
        }

        if ($existing !== null) {
            $connection->table('product_dimensions')->where('id', (int) $existing['id'])->update($values + ['updated_at' => $now]);
        } else {
            $connection->table('product_dimensions')->insert($values + [
                'product_id' => $productId,
                'variant_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /** @param array<string, array<string, mixed>> $seoByLocale */
    private function syncSeoAll(int $productId, array $seoByLocale): void
    {
        $connection = Product::query()->connection();
        $now = date('Y-m-d H:i:s');
        $columns = ['meta_title', 'meta_description', 'og_title', 'og_description', 'canonical_url'];

        foreach ($this->locales() as $locale) {
            $row = is_array($seoByLocale[$locale] ?? null) ? $seoByLocale[$locale] : [];
            $fields = [];
            foreach ($columns as $column) {
                $fields[$column] = $this->nullableTrimmed($row[$column] ?? null, $column === 'meta_description' || $column === 'og_description' ? 500 : 255);
            }
            $existing = $connection->selectOne(
                'SELECT id FROM seo_meta WHERE product_id = ? AND locale = ? LIMIT 1',
                [$productId, $locale],
            );
            if (array_filter($fields, static fn ($value): bool => $value !== null) === []) {
                if ($existing !== null) {
                    $connection->affect('DELETE FROM seo_meta WHERE id = ?', [(int) $existing['id']]);
                }
                continue;
            }
            if ($existing !== null) {
                $connection->table('seo_meta')->where('id', (int) $existing['id'])->update($fields + ['updated_at' => $now]);
            } else {
                $connection->table('seo_meta')->insert($fields + [
                    'product_id' => $productId,
                    'locale' => $locale,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    /**
     * Colour-family edges are an undirected graph: saving the checked members
     * adds missing edges and removes this product's edges to unchecked
     * members (both directions). Seat-cover edges are directional and are
     * replaced per role (covers of this WC / WCs this cover belongs to).
     *
     * @param array<string, mixed> $relations
     */
    private function syncRelations(int $productId, array $relations): void
    {
        $connection = Product::query()->connection();

        $sanitize = function (mixed $ids) use ($connection, $productId): array {
            $ids = array_values(array_unique(array_filter(array_map('intval', is_array($ids) ? $ids : []))));
            $ids = array_values(array_filter($ids, static fn (int $id): bool => $id > 0 && $id !== $productId));
            if ($ids === []) {
                return [];
            }
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $existing = array_map(
                static fn (array $row): int => (int) $row['id'],
                $connection->select("SELECT id FROM products WHERE deleted_at IS NULL AND id IN ({$placeholders})", $ids),
            );

            return array_values(array_intersect($ids, $existing));
        };

        /* ---- colour family (undirected) ---- */
        $desiredColors = $sanitize($relations['color_variant_ids'] ?? []);
        $currentNeighbors = [];
        foreach ($connection->select(
            "SELECT product_id, related_product_id FROM product_relations
             WHERE relation_type = 'color_variant' AND (product_id = ? OR related_product_id = ?)",
            [$productId, $productId],
        ) as $edge) {
            $neighbor = (int) $edge['product_id'] === $productId ? (int) $edge['related_product_id'] : (int) $edge['product_id'];
            if ($neighbor !== $productId) {
                $currentNeighbors[$neighbor] = true;
            }
        }
        foreach (array_keys($currentNeighbors) as $neighbor) {
            if (!in_array($neighbor, $desiredColors, true)) {
                $connection->affect(
                    "DELETE FROM product_relations WHERE relation_type = 'color_variant'
                       AND ((product_id = ? AND related_product_id = ?) OR (product_id = ? AND related_product_id = ?))",
                    [$productId, $neighbor, $neighbor, $productId],
                );
            }
        }
        foreach ($desiredColors as $neighbor) {
            if (!isset($currentNeighbors[$neighbor])) {
                $connection->table('product_relations')->insert([
                    'product_id' => $productId,
                    'related_product_id' => $neighbor,
                    'relation_type' => 'color_variant',
                ]);
            }
        }

        /* ---- seat covers belonging to this product (as a WC) ---- */
        $desiredCovers = $sanitize($relations['seat_cover_ids'] ?? []);
        $connection->affect(
            "DELETE FROM product_relations WHERE product_id = ? AND relation_type = 'seat_cover'"
            . ($desiredCovers !== [] ? ' AND related_product_id NOT IN (' . implode(',', array_fill(0, count($desiredCovers), '?')) . ')' : ''),
            [$productId, ...$desiredCovers],
        );
        foreach ($desiredCovers as $coverId) {
            $exists = $connection->selectOne(
                "SELECT id FROM product_relations WHERE product_id = ? AND related_product_id = ? AND relation_type = 'seat_cover' LIMIT 1",
                [$productId, $coverId],
            );
            if ($exists === null) {
                $connection->table('product_relations')->insert([
                    'product_id' => $productId,
                    'related_product_id' => $coverId,
                    'relation_type' => 'seat_cover',
                ]);
            }
            /* A linked cover is no longer standalone-hidden. */
            $connection->affect(
                "DELETE FROM product_relations WHERE product_id = ? AND related_product_id = ? AND relation_type = 'seat_cover_only'",
                [$coverId, $coverId],
            );
        }

        /* ---- WCs this product is a seat cover for ---- */
        $desiredWcs = $sanitize($relations['wc_ids'] ?? []);
        $connection->affect(
            "DELETE FROM product_relations WHERE related_product_id = ? AND relation_type = 'seat_cover'"
            . ($desiredWcs !== [] ? ' AND product_id NOT IN (' . implode(',', array_fill(0, count($desiredWcs), '?')) . ')' : ''),
            [$productId, ...$desiredWcs],
        );
        foreach ($desiredWcs as $wcId) {
            $exists = $connection->selectOne(
                "SELECT id FROM product_relations WHERE product_id = ? AND related_product_id = ? AND relation_type = 'seat_cover' LIMIT 1",
                [$wcId, $productId],
            );
            if ($exists === null) {
                $connection->table('product_relations')->insert([
                    'product_id' => $wcId,
                    'related_product_id' => $productId,
                    'relation_type' => 'seat_cover',
                ]);
            }
        }

        /* ---- standalone-hidden flag for an unlinked cover ---- */
        $only = !empty($relations['seat_cover_only']) && $desiredWcs === [];
        $existingOnly = $connection->selectOne(
            "SELECT id FROM product_relations WHERE product_id = ? AND related_product_id = ? AND relation_type = 'seat_cover_only' LIMIT 1",
            [$productId, $productId],
        );
        if ($only && $existingOnly === null) {
            $connection->table('product_relations')->insert([
                'product_id' => $productId,
                'related_product_id' => $productId,
                'relation_type' => 'seat_cover_only',
            ]);
        } elseif (!$only && $existingOnly !== null) {
            $connection->affect('DELETE FROM product_relations WHERE id = ?', [(int) $existingOnly['id']]);
        }
    }

    /* ------------------------------------------------------------- internals */

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

    /** @return array<string, mixed>|null */
    private function defaultVariantRow(int $productId): ?array
    {
        return Product::query()->connection()->selectOne(
            'SELECT * FROM product_variants WHERE product_id = ? AND deleted_at IS NULL ORDER BY sort_order ASC, id ASC LIMIT 1',
            [$productId],
        );
    }

    /** @return array<string, mixed> */
    private function ensureDefaultVariant(int $productId, string $modelCode): array
    {
        $variant = $this->defaultVariantRow($productId);
        if ($variant !== null) {
            return $variant;
        }
        $this->syncDefaultVariant($productId, $modelCode, 0.0);

        return $this->defaultVariantRow($productId) ?? [];
    }

    private function refreshArtifacts(int $productId): void
    {
        $connection = Product::query()->connection();
        $row = $connection->selectOne('SELECT model_code, category_id FROM products WHERE id = ?', [$productId]);
        if ($row === null) {
            return;
        }
        $fallback = (string) config('localization.fallback', 'en');
        $nameRow = $connection->selectOne(
            'SELECT name FROM product_translations WHERE product_id = ? AND locale = ? LIMIT 1',
            [$productId, $fallback],
        );
        $this->syncCategoryArtifacts(
            $productId,
            !empty($row['category_id']) ? (int) $row['category_id'] : null,
            (string) ($row['model_code'] ?? ''),
            (string) ($nameRow['name'] ?? $row['model_code'] ?? ''),
        );
    }

    private function isActiveLeaf(int $categoryId): bool
    {
        $connection = Product::query()->connection();
        $category = $connection->selectOne(
            "SELECT id FROM categories WHERE id = ? AND deleted_at IS NULL AND status = 'active' LIMIT 1",
            [$categoryId],
        );
        if ($category === null) {
            return false;
        }
        $child = $connection->selectOne(
            "SELECT id FROM categories WHERE parent_id = ? AND deleted_at IS NULL AND status = 'active' LIMIT 1",
            [$categoryId],
        );

        return $child === null;
    }

    private function categoryPathLabel(int $categoryId): string
    {
        $connection = Product::query()->connection();
        $names = [];
        $visited = [];
        $currentId = $categoryId;
        while ($currentId > 0 && !isset($visited[$currentId])) {
            $visited[$currentId] = true;
            $row = $connection->selectOne(
                "SELECT c.parent_id, c.slug, COALESCE(ct.name, ctf.name, c.slug) AS name
                 FROM categories c
                 LEFT JOIN category_translations ct ON ct.category_id = c.id AND ct.locale = 'en'
                 LEFT JOIN category_translations ctf ON ctf.category_id = c.id AND ctf.locale = 'en'
                 WHERE c.id = ? AND c.deleted_at IS NULL LIMIT 1",
                [$currentId],
            );
            if ($row === null) {
                break;
            }
            array_unshift($names, (string) $row['name']);
            $currentId = !empty($row['parent_id']) ? (int) $row['parent_id'] : 0;
        }

        return implode(' → ', $names);
    }

    /** @return string[] */
    private function locales(): array
    {
        $supported = config('localization.supported', []);
        $locales = is_array($supported) ? array_map('strval', array_keys($supported)) : [];

        return $locales !== [] ? $locales : ['en', 'tr', 'cs'];
    }

    private function inlineLocale(string $locale): string
    {
        return in_array($locale, $this->locales(), true) ? $locale : (string) config('localization.fallback', 'en');
    }

    /**
     * @param mixed $values locale => string map from the form
     * @param string[] $locales
     * @return array<string, string>
     */
    private function localeMap(mixed $values, array $locales, int $maxLength): array
    {
        $out = [];
        foreach ($locales as $locale) {
            $value = is_array($values) ? trim((string) ($values[$locale] ?? '')) : '';
            $out[$locale] = mb_substr($value, 0, $maxLength);
        }

        return $out;
    }

    /** @param array<string, string> $map */
    private function firstNonEmpty(array $map, string $fallback): string
    {
        if (($map[$fallback] ?? '') !== '') {
            return $map[$fallback];
        }
        foreach ($map as $value) {
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private function nullableTrimmed(mixed $value, int $maxLength): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value !== '' ? mb_substr($value, 0, $maxLength) : null;
    }

    private function nullableNumber(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    private function trimNumber(float $value): string
    {
        $formatted = number_format($value, 2, '.', '');

        return rtrim(rtrim($formatted, '0'), '.');
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
        /* Linked seat-cover codes find their parent WC in live search. */
        foreach ($connection->select(
            "SELECT p.model_code FROM product_relations pr
             INNER JOIN products p ON p.id = pr.related_product_id AND p.deleted_at IS NULL
             WHERE pr.product_id = ? AND pr.relation_type = 'seat_cover'",
            [$productId],
        ) as $seat) {
            $code = trim((string) ($seat['model_code'] ?? ''));
            if ($code !== '') {
                $keywords[] = mb_strtolower($code);
            }
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
