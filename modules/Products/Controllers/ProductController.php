<?php

declare(strict_types=1);

namespace Modules\Products\Controllers;

use App\Http\Controllers\Controller;
use App\Services\CategoryService;
use App\Services\MediaService;
use App\Services\SEOService;
use Core\Http\JsonResponse;
use Core\Http\RedirectResponse;
use Core\Http\Request;
use Core\Http\Response;
use Core\Localization\Translator;
use InvalidArgumentException;
use Modules\Products\Models\Product;
use Modules\Products\Requests\StoreProductRequest;
use Modules\Products\Requests\UpdateProductRequest;
use Modules\Products\Services\ProductCardPresenter;
use Modules\Products\Services\ProductColorFamilyService;
use Modules\Products\Services\ProductFeatureIconCatalog;
use Modules\Products\Services\ProductMediaService;
use Modules\Products\Services\ProductService;
use Modules\Products\Services\ProductVisibility;
use Throwable;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $products,
        private readonly ProductMediaService $media,
        private readonly SEOService $seo,
        private readonly CategoryService $categories,
        private readonly Translator $translator,
        private readonly MediaService $mediaLibrary,
    ) {
    }

    public function index(Request $request): Response
    {
        $locale = $this->translator->getLocale();
        $categorySlug = (string) $request->query('category', '');
        $categoryId = (int) $request->query('category_id', '0');
        $searchQuery = (string) $request->query('q', '');
        $sort = (string) $request->query('sort', 'curated');
        if (!in_array($sort, ['curated', 'newest', 'name', 'model'], true)) {
            $sort = 'curated';
        }
        $fallback = (string) config('localization.fallback', 'en');

        $paginator = $this->products->paginate([
            'locale' => $locale,
            'status' => 'active',
            'category_slug' => $categorySlug,
            'category_id' => $categoryId,
            'search' => $searchQuery,
            'sort' => $sort,
        ], (int) $request->query('page', '1'), 12);

        $categoryCounts = $this->categories->productCounts();
        $categories = $this->categories->nonEmptyTree($locale, $fallback, $categoryCounts);
        $categoryFallbackName = null;

        $pageTitle = trans('products.title');
        $catalogDescs = [
            'en' => 'Browse the complete architectural catalog of luxury sanitary ware, rimless toilets, and PVD brassware from LUFLY.',
            'tr' => 'LUFLY lüks vitrifiye seramikler, asma klozetler ve mimari bataryaların eksiksiz ürün kataloğunu inceleyin.',
            'cs' => 'Prohlédněte si kompletní architektonický katalog prémiové sanitární keramiky a baterií LUFLY.',
        ];

        $this->seo->setTitle($pageTitle);
        $this->seo->setDescription($catalogDescs[$locale] ?? $catalogDescs['en']);
        $this->seo->setCanonical(route('products.index'));

        /* Faceted states (filters, search, sorting, pagination) share the
           canonical of the clean catalogue URL and stay out of the index:
           crawlers may follow them, but only one catalog page ranks. */
        $isFaceted = $categorySlug !== '' || $categoryId > 0 || $searchQuery !== ''
            || $sort !== 'curated' || (int) $request->query('page', '1') > 1;
        if ($isFaceted) {
            $this->seo->setRobots('noindex, follow');
        }
        $this->seo->setAlternatesFor('products.index');

        if (!$isFaceted) {
            /* ItemList rich result for the first visible products. */
            $listItems = [];
            foreach (array_slice($paginator->items(), 0, 20) as $item) {
                $translated = is_object($item) && method_exists($item, 'translate')
                    ? $item->translate($locale)
                    : (array) $item;
                $slug = (string) ($translated['slug'] ?? '');
                if ($slug === '') {
                    continue;
                }
                $listItems[] = [
                    'url' => route('products.show', ['slug' => $slug]),
                    'name' => (string) ($translated['name'] ?? $slug),
                    'image' => !empty($translated['image']) ? asset((string) $translated['image']) : null,
                ];
            }
            if ($listItems !== []) {
                $this->seo->addItemListSchema($listItems, $pageTitle);
            }
            $this->seo->addWebPageSchema([
                'type' => 'CollectionPage',
                'name' => $pageTitle,
                'description' => $catalogDescs[$locale] ?? $catalogDescs['en'],
                'url' => route('products.index'),
                'inLanguage' => $locale,
            ]);
        }

        return $this->view('products::index', [
            'title' => $pageTitle,
            'paginator' => $paginator,
            'categories' => $categories,
            'categoryCounts' => $categoryCounts,
            'activeCategory' => $categorySlug,
            'categoryFallbackName' => $categoryFallbackName,
            'searchQuery' => $searchQuery,
            'sort' => $sort,
            'locale' => $locale,
            'seo' => $this->seo,
        ]);
    }

    public function show(string $slug): Response
    {
        $locale = $this->translator->getLocale();

        /* Toilet seat covers never appear as standalone catalog pages: direct
           visits redirect cleanly to the parent WC's #wc-seat-options block. */
        $rawCandidate = \Modules\Products\Models\Product::query()
            ->where('slug', $slug)
            ->where('status', 'active')
            ->first();
        if ($rawCandidate instanceof \Modules\Products\Models\Product && ProductVisibility::isSeatCover($rawCandidate)) {
            $parentWcSlug = ProductVisibility::parentWcSlug((int) $rawCandidate->getKey());
            if ($parentWcSlug !== null && $parentWcSlug !== '') {
                return new RedirectResponse(route('products.show', ['slug' => $parentWcSlug]) . '#wc-seat-options');
            }
        }

        $product = app(ProductColorFamilyService::class)->attach(
            $this->products->findTranslatedBySlug($slug, $locale),
            $locale,
        );

        $cardPresentation = ProductCardPresenter::make($product, $locale);
        $product['presented_features'] = $cardPresentation['specs'];
        if (!empty($product['seat_covers']) && is_array($product['seat_covers'])) {
            $product['seat_covers'] = array_map(
                static function (array $seat) use ($locale): array {
                    $seatCard = ProductCardPresenter::make($seat, $locale);
                    $seat['presented_features'] = $seatCard['specs'];
                    return $seat;
                },
                $product['seat_covers'],
            );
        }

        /* Category slug drives the technical-sheet silhouette on the page. */
        $categorySlug = (string) ($product['category_slug'] ?? '');
        if ($categorySlug === '' && !empty($product['category_id'])) {
            $connection = \Modules\Products\Models\Product::query()->connection();
            $row = $connection->selectOne(
                'SELECT slug FROM categories WHERE id = ? AND deleted_at IS NULL',
                [(int) $product['category_id']],
            );
            $categorySlug = (string) ($row['slug'] ?? '');
        }
        $product['category_slug'] = $categorySlug;
        $categoryLabel = trim((string) ($product['category_name'] ?? ''));
        $fallback = (string) config('localization.fallback', 'en');
        $categoryBreadcrumbs = !empty($product['category_id'])
            ? $this->categories->breadcrumbsForCategoryId((int) $product['category_id'], $locale, $fallback)
            : [];

        $productName = trim((string) ($product['name'] ?? ''));
        if ($productName === '') {
            $productName = trans('products.name_pending');
        }
        $productDesc = (string) ($product['short_description'] ?? $product['description'] ?? '');

        $canonical = route('products.show', ['slug' => $product['slug'] ?? $slug]);

        $this->seo->setTitle($productName);
        $this->seo->setDescription($productDesc);
        $this->seo->setFromEntity($product);
        $this->seo->setCanonical($canonical);
        $this->seo->setType('product');
        $this->seo->setAlternatesFor('products.show', ['slug' => $product['slug'] ?? $slug]);

        /* share card: dynamically rendered OG image carrying the product name */
        $this->seo->setImage(route('seo.ogimage', [
            'title' => $productName,
            'subtitle' => $categoryLabel !== ''
                ? 'LUFLY ' . $categoryLabel
                : ($product['category_slug'] !== '' ? 'LUFLY ' . ucfirst((string) $product['category_slug']) : 'LUFLY'),
        ]));

        $this->seo->addProductSchema($product, $canonical);
        $this->seo->addWebPageSchema([
            'name' => $productName,
            'description' => $productDesc,
            'url' => $canonical,
            'inLanguage' => $locale,
        ]);
        $seoBreadcrumbs = [
            ['name' => 'LUFLY', 'url' => route('home')],
            ['name' => trans('products.title'), 'url' => route('products.index')],
        ];
        foreach ($categoryBreadcrumbs as $crumb) {
            $seoBreadcrumbs[] = [
                'name' => $crumb['name'],
                'url' => route('products.index', ['category' => $crumb['slug']]),
            ];
        }
        $seoBreadcrumbs[] = ['name' => $productName, 'url' => $canonical];
        $this->seo->addBreadcrumb($seoBreadcrumbs);

        $savedIds = [];
        if (feature('favorites', true) && class_exists(\Modules\Favorites\Services\FavoriteService::class)) {
            $savedIds = array_flip(app(\Modules\Favorites\Services\FavoriteService::class)->productIds());
        }
        $boxIds = [];
        if (feature('box', true) && class_exists(\Modules\Box\Services\BoxService::class)) {
            $boxIds = array_flip(app(\Modules\Box\Services\BoxService::class)->productIds());
        }

        $colorFamilies = app(ProductColorFamilyService::class);
        $rawRecs = $this->products->marketplaceRecommendations($product, 4, 4);
        $hydrateRecCards = static function (array $models) use ($colorFamilies, $locale, $savedIds, $boxIds): array {
            $cards = [];
            foreach ($models as $recModel) {
                $cards[] = [
                    'product' => $colorFamilies->attach($recModel->translate($locale), $locale, $savedIds, $boxIds),
                    'slug' => (string) $recModel->slug,
                    'saved' => isset($savedIds[(int) $recModel->getKey()]),
                    'in_box' => isset($boxIds[(int) $recModel->getKey()]),
                ];
            }
            return $cards;
        };

        $productId = (int) ($product['id'] ?? 0);

        return $this->view('products::show', [
            'title' => $productName,
            'product' => $product,
            'card' => $cardPresentation,
            'saved' => isset($savedIds[$productId]),
            'inBox' => isset($boxIds[$productId]),
            'savedIds' => $savedIds,
            'boxIds' => $boxIds,
            'datasheetUrl' => $this->products->datasheetUrl($productId, $locale),
            'categoryNeighbors' => $this->products->categoryNeighbors($product),
            'similarProducts' => $hydrateRecCards($rawRecs['similar']),
            'complementaryProducts' => $hydrateRecCards($rawRecs['complementary']),
            'categoryBreadcrumbs' => $categoryBreadcrumbs,
            'locale' => $locale,
            'seo' => $this->seo,
        ]);
    }

    /* ------------------------------------------------------- admin panel */

    public function adminIndex(Request $request): Response
    {
        $locale = (string) config('localization.fallback', 'en');
        $search = trim((string) $request->query('q', ''));
        $categoryId = max(0, (int) $request->query('category_id', '0'));
        $status = (string) $request->query('status', '');
        if (!in_array($status, ProductService::STATUSES, true)) {
            $status = '';
        }
        $featured = (string) $request->query('featured', '') === '1';
        $sort = (string) $request->query('sort', 'model');
        if (!in_array($sort, ['model', 'name', 'curated', 'newest'], true)) {
            $sort = 'model';
        }
        $page = max(1, (int) $request->query('page', '1'));

        $paginator = $this->products->paginate([
            'search' => $search,
            'locale' => $locale,
            'status' => $status,
            'category_id' => $categoryId,
            'featured' => $featured,
            'sort' => $sort,
        ], $page, 24);

        $items = $paginator->items();
        Product::eagerLoad($items, ['translations', 'variants', 'specs', 'card_features', 'dimensions', 'media', 'category']);

        $ids = [];
        $cards = [];
        $payloads = [];
        $mediaState = [];
        foreach ($items as $product) {
            $productId = (int) $product->id;
            $ids[] = $productId;
            $payloads[$productId] = $product->translate($locale);
            $cards[$productId] = ProductCardPresenter::make($payloads[$productId], $locale);
            $mediaState[$productId] = ProductVisibility::media($product);
        }

        $categoryOptions = $this->categories->flatten($locale, $locale);
        $categoryPaths = [];
        foreach ($categoryOptions as $option) {
            $categoryPaths[(int) $option['id']] = (string) ($option['path'] ?? $option['slug']);
        }

        return $this->view('products::Admin.index', [
            'title' => trans('common.products'),
            'paginator' => $paginator,
            'cards' => $cards,
            'payloads' => $payloads,
            'mediaState' => $mediaState,
            'chips' => $this->products->cardChipsBulk($ids, $locale),
            'finishSpecs' => $this->products->finishSpecsBulk($ids),
            'categoryOptions' => $categoryOptions,
            'categoryPaths' => $categoryPaths,
            'filters' => [
                'q' => $search,
                'category_id' => $categoryId,
                'status' => $status,
                'featured' => $featured,
                'sort' => $sort,
            ],
            'locale' => $locale,
        ]);
    }

    public function adminCreate(): Response
    {
        return $this->view('products::Admin.form', [
            'title' => trans('common.create_product'),
            ...$this->editorData(null),
        ]);
    }

    public function adminStore(Request $request): RedirectResponse
    {
        $data = (new StoreProductRequest())->handle($request);

        try {
            $product = $this->products->create(
                $this->coreFields($data),
                $this->translationsFromForm($data),
                $this->extraFrom($request),
            );
        } catch (InvalidArgumentException $e) {
            return $this->redirect(route('admin.products.create'))
                ->with('_errors', ['product' => [$e->getMessage()]])
                ->withInput($request->all());
        }

        if ($request->hasFile('image')) {
            $this->attachPrimaryImage((int) $product->id, $request->file('image'));
        }

        try {
            $this->syncDatasheets($request, (int) $product->id);
        } catch (Throwable $e) {
            return $this->redirect(route('admin.products.edit', ['id' => $product->id]))
                ->with('_errors', ['datasheet' => [$e->getMessage()]]);
        }

        return $this->redirect(route('admin.products.edit', ['id' => $product->id]))
            ->with('_success', trans('common.saved'));
    }

    public function adminEdit(int $id): Response
    {
        $product = $this->products->find($id);

        return $this->view('products::Admin.form', [
            'title' => trans('common.edit_product'),
            ...$this->editorData($product),
        ]);
    }

    public function adminUpdate(Request $request, int $id): RedirectResponse
    {
        $data = (new UpdateProductRequest())->forProduct($id)->handle($request);

        try {
            $this->products->update($id, $this->coreFields($data), $this->translationsFromForm($data), $this->extraFrom($request));
        } catch (InvalidArgumentException $e) {
            return $this->redirect(route('admin.products.edit', ['id' => $id]))
                ->with('_errors', ['product' => [$e->getMessage()]])
                ->withInput($request->all());
        }

        if ($request->hasFile('image')) {
            $this->attachPrimaryImage($id, $request->file('image'));
        }

        try {
            $this->syncDatasheets($request, $id);
        } catch (Throwable $e) {
            return $this->redirect(route('admin.products.edit', ['id' => $id]))
                ->with('_errors', ['datasheet' => [$e->getMessage()]]);
        }

        return $this->redirect(route('admin.products.edit', ['id' => $id]))
            ->with('_success', trans('common.saved'));
    }

    public function adminInline(Request $request, int $id): JsonResponse
    {
        $payload = $request->jsonPayload() ?? $request->all();

        try {
            $result = $this->products->inlineUpdate($id, is_array($payload) ? $payload : []);
        } catch (InvalidArgumentException $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return $this->json(['success' => true, 'data' => $result]);
    }

    public function adminToggleFeatured(Request $request, int $id): Response
    {
        $result = $this->products->toggleFeatured($id);

        if ($request->expectsJson()) {
            return $this->json(['success' => true, 'data' => $result]);
        }

        $query = array_filter([
            'page' => max(1, (int) $request->input('page', '1')),
            'q' => trim((string) $request->input('q', '')),
            'category_id' => (int) $request->input('category_id', '0') ?: null,
            'status' => trim((string) $request->input('status', '')) ?: null,
            'featured' => (string) $request->input('featured', '') === '1' ? 1 : null,
        ], static fn ($value): bool => $value !== null && $value !== '');

        return $this->redirect(route('admin.products.index') . '?' . http_build_query($query))
            ->with('_success', trans('common.saved'));
    }

    public function adminSearch(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));
        $exclude = max(0, (int) $request->query('exclude', '0'));

        return $this->json([
            'success' => true,
            'data' => $query === '' ? [] : $this->products->adminSearch($query, $exclude, 20),
        ]);
    }

    public function adminToggleVisibility(Request $request, int $id): RedirectResponse
    {
        $product = $this->products->find($id);
        $status = (string) $product->status;
        $query = [
            'page' => max(1, (int) $request->input('page', '1')),
            'q' => trim((string) $request->input('q', '')),
        ];
        $target = route('admin.products.index') . '?' . http_build_query($query);

        /* Active/hidden is the existing public-visibility switch. Other
           lifecycle states stay under the full edit form so toggling display
           never silently turns Coming soon or Discontinued into Active. */
        if (!in_array($status, ['active', 'hidden'], true)) {
            return $this->redirect($target)->with('_errors', [
                'status' => [trans('products.visibility_toggle_invalid')],
            ]);
        }
        if (!ProductVisibility::media($product)['ready']) {
            return $this->redirect($target)->with('_errors', [
                'status' => [trans('products.visibility_media_required')],
            ]);
        }

        $this->products->update($id, [
            'status' => $status === 'active' ? 'hidden' : 'active',
        ]);

        return $this->redirect($target)->with('_success', trans('products.visibility_updated'));
    }

    public function adminDestroy(int $id): RedirectResponse
    {
        $this->products->delete($id);

        return $this->redirect(route('admin.products.index'))
            ->with('_success', trans('common.deleted'));
    }

    /**
     * Everything the full product editor renders, for create and edit alike.
     *
     * @return array<string, mixed>
     */
    private function editorData(?Product $product): array
    {
        $productId = $product !== null ? (int) $product->id : 0;
        $relationState = $product !== null
            ? $this->products->relationState($productId)
            : ['color_family' => [], 'seat_covers' => [], 'parent_wcs' => [], 'seat_cover_only' => false];
        $relationIds = array_values(array_unique([
            ...$relationState['color_family'],
            ...$relationState['seat_covers'],
            ...$relationState['parent_wcs'],
        ]));

        return [
            'product' => $product,
            'mediaSections' => $product !== null ? $product->mediaBySection() : [],
            'translations' => $product !== null ? $product->translations() : [],
            'specs' => $product !== null ? $product->specs() : [],
            'features' => $product !== null ? $this->products->cardFeaturesRaw($productId) : [],
            'presets' => $this->products->featurePresets(),
            'icons' => ProductFeatureIconCatalog::options('en'),
            'finishCatalog' => ProductCardPresenter::finishCatalog(),
            'variants' => $product !== null ? $product->variants() : [],
            'dimensions' => $product !== null ? $product->dimensions() : null,
            'seoRows' => $product !== null ? $this->products->seoRows($productId) : [],
            'relationState' => $relationState,
            'relationLabels' => $this->products->pickerLabels($relationIds),
            'datasheets' => $product !== null ? $this->products->datasheets($productId) : [],
        ];
    }

    /**
     * Nested editor payloads (variants / specs / features / dimensions / seo /
     * relations), lightly sanitized; ProductService owns the invariants.
     *
     * @return array<string, mixed>
     */
    private function extraFrom(Request $request): array
    {
        $all = $request->all();
        $extra = [];

        if (isset($all['variants']) && is_array($all['variants'])) {
            $rows = [];
            foreach ($all['variants'] as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $id = (int) ($row['id'] ?? 0);
                $sku = trim((string) ($row['sku'] ?? ''));
                $name = trim((string) ($row['variant_name'] ?? ''));
                if ($id === 0 && $sku === '' && $name === '') {
                    continue;
                }
                $rows[] = [
                    'id' => $id,
                    'sku' => $sku,
                    'variant_name' => $name,
                    'price' => $row['price'] ?? 0,
                    'stock_status' => (string) ($row['stock_status'] ?? 'in_stock'),
                    'status' => (string) ($row['status'] ?? 'active'),
                    'sort_order' => $row['sort_order'] ?? null,
                ];
            }
            $extra['variants'] = $rows;
        }

        if (isset($all['specs']) && is_array($all['specs'])) {
            $rows = [];
            foreach ($all['specs'] as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $keys = is_array($row['key'] ?? null) ? $row['key'] : [];
                $values = is_array($row['value'] ?? null) ? $row['value'] : [];
                $hasContent = false;
                foreach ([...array_values($keys), ...array_values($values)] as $piece) {
                    if (trim((string) $piece) !== '') {
                        $hasContent = true;
                        break;
                    }
                }
                if (!$hasContent && (int) ($row['id'] ?? 0) === 0) {
                    continue;
                }
                $rows[] = [
                    'id' => (int) ($row['id'] ?? 0),
                    'unit' => (string) ($row['unit'] ?? ''),
                    'key' => $keys,
                    'value' => $values,
                ];
            }
            $extra['specs'] = $rows;
        }

        if (isset($all['features']) && is_array($all['features'])) {
            $rows = [];
            foreach ($all['features'] as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $labels = is_array($row['label'] ?? null) ? $row['label'] : [];
                $values = is_array($row['value'] ?? null) ? $row['value'] : [];
                $hasContent = (int) ($row['preset_id'] ?? 0) > 0;
                if (!$hasContent) {
                    foreach ([...array_values($labels), ...array_values($values)] as $piece) {
                        if (trim((string) $piece) !== '') {
                            $hasContent = true;
                            break;
                        }
                    }
                }
                if (!$hasContent && (int) ($row['id'] ?? 0) === 0) {
                    continue;
                }
                $rows[] = [
                    'id' => (int) ($row['id'] ?? 0),
                    'preset_id' => (int) ($row['preset_id'] ?? 0),
                    'icon_key' => (string) ($row['icon_key'] ?? ''),
                    'label' => $labels,
                    'value' => $values,
                ];
            }
            $extra['features'] = $rows;
        }

        if (array_key_exists('dim_width_mm', $all) || array_key_exists('dim_height_mm', $all)
            || array_key_exists('dim_depth_mm', $all) || array_key_exists('dim_weight_kg', $all)) {
            $extra['dimensions'] = [
                'width_mm' => $all['dim_width_mm'] ?? null,
                'height_mm' => $all['dim_height_mm'] ?? null,
                'depth_mm' => $all['dim_depth_mm'] ?? null,
                'weight_kg' => $all['dim_weight_kg'] ?? null,
            ];
        }

        if (isset($all['seo']) && is_array($all['seo'])) {
            $extra['seo'] = $all['seo'];
        }

        if (($all['relations_present'] ?? '') === '1') {
            $ids = static fn (mixed $values): array => array_values(array_filter(array_map('intval', is_array($values) ? $values : [])));
            $extra['relations'] = [
                'color_variant_ids' => $ids($all['color_variant_ids'] ?? []),
                'seat_cover_ids' => $ids($all['seat_cover_ids'] ?? []),
                'wc_ids' => $ids($all['wc_ids'] ?? []),
                'seat_cover_only' => ($all['seat_cover_only'] ?? '') === '1',
            ];
        }

        return $extra;
    }

    /**
     * Datasheet PDF uploads/removals per language. Uploading a file activates
     * the storefront Data Sheet button automatically (product_documents).
     */
    private function syncDatasheets(Request $request, int $productId): void
    {
        $files = [];
        $entry = $request->file('datasheet');
        if ($entry !== null && isset($entry['name']) && is_array($entry['name'])) {
            foreach (array_keys($entry['name']) as $locale) {
                $file = [
                    'name' => $entry['name'][$locale] ?? '',
                    'type' => $entry['type'][$locale] ?? '',
                    'tmp_name' => $entry['tmp_name'][$locale] ?? '',
                    'error' => $entry['error'][$locale] ?? UPLOAD_ERR_NO_FILE,
                    'size' => $entry['size'][$locale] ?? 0,
                ];
                if ((int) $file['error'] === UPLOAD_ERR_OK) {
                    $files[(string) $locale] = $file;
                } elseif ((int) $file['error'] !== UPLOAD_ERR_NO_FILE) {
                    throw new InvalidArgumentException(trans('products.datasheet_upload_failed'));
                }
            }
        }

        $deletes = $request->input('datasheet_delete', []);
        $deletes = is_array($deletes) ? $deletes : [];

        foreach ($this->translator->locales() as $locale) {
            if (isset($files[$locale])) {
                $extension = strtolower(pathinfo((string) $files[$locale]['name'], PATHINFO_EXTENSION));
                if ($extension !== 'pdf') {
                    throw new InvalidArgumentException(trans('products.datasheet_pdf_only'));
                }
                $media = $this->mediaLibrary->storeFromUpload($files[$locale], 'datasheets', $productId);
                $detached = $this->products->attachDatasheet($productId, $locale, (int) $media->id);
            } elseif (($deletes[$locale] ?? '') === '1') {
                $detached = $this->products->detachDatasheet($productId, $locale);
            } else {
                continue;
            }

            foreach ($detached as $mediaId) {
                try {
                    $this->mediaLibrary->delete($mediaId);
                } catch (Throwable) {
                    /* The document row is already detached; a stale file is harmless. */
                }
            }
        }
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function coreFields(array $data): array
    {
        $fields = [
            'model_code' => $data['model_code'],
            'slug' => $data['slug'] ?? '',
            'category_id' => ($data['category_id'] ?? '') !== '' ? (int) $data['category_id'] : null,
            'collection_id' => ($data['collection_id'] ?? '') !== '' ? (int) $data['collection_id'] : null,
            'brand_id' => ($data['brand_id'] ?? '') !== '' ? (int) $data['brand_id'] : null,
            'is_featured' => (string) ($data['is_featured'] ?? '0'),
            'status' => $data['status'],
            'meta_title' => $data['meta_title'] ?? '',
            'meta_description' => $data['meta_description'] ?? '',
        ];
        if (array_key_exists('price', $data) && $data['price'] !== '' && $data['price'] !== null) {
            $fields['price'] = (float) $data['price'];
        }
        if (array_key_exists('sort_order', $data) && $data['sort_order'] !== '' && $data['sort_order'] !== null) {
            $fields['sort_order'] = max(0, (int) $data['sort_order']);
        }

        return $fields;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, array<string, mixed>>
     */
    private function translationsFromForm(array $data): array
    {
        $translations = [];
        foreach ($this->translator->locales() as $locale) {
            $name = $data['name_' . $locale] ?? '';
            $description = $data['description_' . $locale] ?? '';
            if ($name !== '' || $description !== '') {
                $translations[$locale] = [
                    'name' => $name,
                    'description' => $description,
                    'short_description' => (string) ($data['short_description_' . $locale] ?? '') !== ''
                        ? $data['short_description_' . $locale]
                        : mb_substr((string) $description, 0, 160),
                ];
            }
        }

        return $translations;
    }

    /**
     * Upload coming from the single-image field of the product form: it lands
     * in the photos section and becomes the main image.
     *
     * @param array<string, mixed>|null $file
     */
    private function attachPrimaryImage(int $productId, ?array $file): void
    {
        if ($file === null) {
            return;
        }
        $this->media->upload($productId, $file, 'photos', true);
    }
}
