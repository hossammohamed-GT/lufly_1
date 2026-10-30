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

    /** @var array<int, array<string, mixed>>|null memoized flat category tree */
    private ?array $flatCategoriesCache = null;

    public function adminIndex(Request $request): Response
    {
        return $this->renderBrowser($request, false);
    }

    /** Products that are missing something before they can shine on the site. */
    public function adminIncomplete(Request $request): Response
    {
        return $this->renderBrowser($request, true);
    }

    /** AJAX: one page of the card grid (HTML fragment inside JSON). */
    public function adminGrid(Request $request): JsonResponse
    {
        $locale = (string) config('localization.fallback', 'en');
        $filters = $this->gridFilters($request);
        $grid = $this->products->adminGrid($filters, (int) $filters['page'], 24);

        $html = app(\Core\View\View::class)->render('products::Admin._grid', [
            ...$this->cardData($grid, $locale),
            'categoryPaths' => $this->categoryPaths(),
            'mode' => !empty($filters['incomplete']) ? 'incomplete' : 'index',
            'locale' => $locale,
        ]);

        return $this->json([
            'success' => true,
            'html' => $html,
            'total' => $grid['total'],
            'page' => $grid['page'],
            'pages' => $grid['pages'],
        ]);
    }

    /** One product card (face + empty editor slot) — colour switching, refreshes, drafts. */
    public function adminCard(int $id): Response
    {
        $locale = (string) config('localization.fallback', 'en');
        $product = $this->products->find($id);
        $family = app(ProductColorFamilyService::class)->familyOf($id);
        $grid = ['ids' => [$id], 'families' => count($family) >= 2 ? [$id => $family] : []];

        return $this->view('products::Admin._card', [
            ...$this->cardData($grid, $locale),
            'product' => $product,
            'categoryPaths' => $this->categoryPaths(),
            'mode' => 'index',
            'locale' => $locale,
        ]);
    }

    /** The expanded in-card editor (all sections, lazy-loaded once per card). */
    public function adminEditor(int $id): Response
    {
        $product = $this->products->find($id);

        return $this->view('products::Admin._editor', $this->editorData($product));
    }

    /** The images part of the editor, refreshed after every media change. */
    public function adminMediaFragment(int $id): Response
    {
        $product = $this->products->find($id);

        return $this->view('products::Admin._media', [
            'product' => $product,
            'mediaSections' => $product->mediaBySection(),
        ]);
    }

    /** Save one editor section (variants, specs, features, …) without a reload. */
    public function adminSection(Request $request, int $id, string $section): JsonResponse
    {
        $this->products->find($id);

        try {
            if ($section === 'documents') {
                $this->syncDatasheets($request, $id);
            } elseif ($section === 'texts') {
                $this->products->syncSection($id, 'texts', [
                    'translations' => $this->translationsFromForm($request->all()),
                ]);
            } elseif ($section === 'classification') {
                $this->products->syncSection($id, 'classification', [
                    'collection_id' => $request->input('collection_id'),
                    'brand_id' => $request->input('brand_id'),
                ]);
            } elseif (in_array($section, ['variants', 'specs', 'features', 'dimensions', 'seo', 'relations'], true)) {
                $extra = $this->extraFrom($request);
                $this->products->syncSection($id, $section, [
                    $section => $extra[$section] ?? ($section === 'dimensions' ? null : []),
                ]);
            } else {
                return $this->json(['success' => false, 'message' => 'Unknown editor section.'], 404);
            }
        } catch (InvalidArgumentException $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return $this->json(['success' => true, 'message' => trans('common.saved')]);
    }

    /** Colour families overview: every multi-colour family with its members. */
    public function adminFamilies(Request $request): Response
    {
        $locale = (string) config('localization.fallback', 'en');
        $familyMap = app(ProductColorFamilyService::class)->familyMap();

        $groups = [];
        $seen = [];
        foreach ($familyMap as $members) {
            $members = array_values(array_map('intval', $members));
            $rep = (int) ($members[0] ?? 0);
            if ($rep <= 0 || isset($seen[$rep])) {
                continue;
            }
            $seen[$rep] = true;
            $groups[] = $members;
        }

        $allIds = [];
        foreach ($groups as $members) {
            $allIds = [...$allIds, ...$members];
        }
        $allIds = array_values(array_unique($allIds));
        $models = $allIds === [] ? [] : Product::query()->whereIn('id', $allIds)->get();
        if ($models !== []) {
            Product::eagerLoad($models, ['translations', 'variants', 'specs', 'card_features', 'dimensions', 'media', 'category']);
        }
        $byId = [];
        foreach ($models as $model) {
            $byId[(int) $model->id] = $model;
        }

        $familiesOut = [];
        foreach ($groups as $members) {
            $rows = [];
            foreach ($members as $memberId) {
                if (!isset($byId[$memberId])) {
                    continue;
                }
                $model = $byId[$memberId];
                $payload = $model->translate($locale);
                $card = ProductCardPresenter::make($payload, $locale);
                $finish = $card['finishes'][0] ?? null;
                $media = ProductVisibility::media($model);
                $cover = $this->coverOf($model);
                $rows[] = [
                    'id' => $memberId,
                    'model_code' => (string) ($payload['model_code'] ?? ''),
                    'name' => (string) ($payload['name'] ?? ''),
                    'finish_key' => (string) ($finish['key'] ?? 'neutral'),
                    'finish_label' => (string) ($finish['label'] ?? ''),
                    'live' => in_array((string) ($payload['status'] ?? $model->status), ProductVisibility::PUBLIC_STATUSES, true) && $media['ready'],
                    'cover' => (string) ($cover['path'] ?? ''),
                ];
            }
            if (count($rows) >= 2) {
                $familiesOut[] = ['source_id' => $rows[0]['id'], 'members' => $rows];
            }
        }
        usort($familiesOut, static fn (array $a, array $b): int => strcmp($a['members'][0]['model_code'], $b['members'][0]['model_code']));

        return $this->view('products::Admin.families', [
            'title' => trans('products.families_title'),
            'families' => $familiesOut,
            'locale' => $locale,
        ]);
    }

    /** Create a draft product from the grid; its card comes back ready to edit. */
    public function adminDraft(Request $request): JsonResponse
    {
        $payload = $request->jsonPayload() ?? $request->all();
        $categoryId = (int) (is_array($payload) ? ($payload['category_id'] ?? 0) : 0);

        try {
            $product = $this->products->createDraft($categoryId > 0 ? $categoryId : null);
        } catch (InvalidArgumentException $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        $locale = (string) config('localization.fallback', 'en');
        $grid = ['ids' => [(int) $product->id], 'families' => []];
        $html = app(\Core\View\View::class)->render('products::Admin._card', [
            ...$this->cardData($grid, $locale),
            'product' => $product,
            'categoryPaths' => $this->categoryPaths(),
            'mode' => 'index',
            'locale' => $locale,
        ]);

        return $this->json(['success' => true, 'id' => (int) $product->id, 'html' => $html]);
    }

    /** Copy a product as a new colour of its family; the copy's card comes back. */
    public function adminAddColor(int $id): JsonResponse
    {
        try {
            $clone = $this->products->cloneAsColorVariant($id);
        } catch (InvalidArgumentException $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        $locale = (string) config('localization.fallback', 'en');
        $grid = ['ids' => [(int) $clone->id], 'families' => []];
        $html = app(\Core\View\View::class)->render('products::Admin._card', [
            ...$this->cardData($grid, $locale),
            'product' => $clone,
            'categoryPaths' => $this->categoryPaths(),
            'mode' => 'index',
            'locale' => $locale,
        ]);

        return $this->json([
            'success' => true,
            'message' => trans('products.family_added'),
            'id' => (int) $clone->id,
            'html' => $html,
        ]);
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

    public function adminDestroy(Request $request, int $id): Response
    {
        $this->products->delete($id);

        if ($request->expectsJson()) {
            return $this->json(['success' => true, 'message' => trans('common.deleted')]);
        }

        return $this->redirect(route('admin.products.index'))
            ->with('_success', trans('common.deleted'));
    }

    /* ------------------------------------------------------- admin helpers */

    private function renderBrowser(Request $request, bool $incomplete): Response
    {
        $locale = (string) config('localization.fallback', 'en');
        $filters = $this->gridFilters($request, $incomplete);
        $grid = $this->products->adminGrid($filters, (int) $filters['page'], 24);

        return $this->view('products::Admin.index', [
            'title' => trans($incomplete ? 'products.incomplete_title' : 'common.products'),
            'mode' => $incomplete ? 'incomplete' : 'index',
            'grid' => $grid,
            'filters' => $filters,
            ...$this->cardData($grid, $locale),
            'categoryPaths' => $this->categoryPaths(),
            'categoryTree' => $this->categoryTree(),
            'presets' => $this->products->featurePresets(),
            'icons' => ProductFeatureIconCatalog::options('en'),
            'finishCatalog' => ProductCardPresenter::finishCatalog(),
            'reasonSummary' => $incomplete ? $this->products->incompleteSummary() : [],
            'incompleteTotal' => $this->products->incompleteCount(),
            'locale' => $locale,
        ]);
    }

    /** @return array<string, mixed> */
    private function gridFilters(Request $request, ?bool $forceIncomplete = null): array
    {
        $status = (string) $request->query('status', '');
        if ($status !== 'not_live' && !in_array($status, ProductService::STATUSES, true)) {
            $status = '';
        }
        $sort = (string) $request->query('sort', 'model');
        if (!in_array($sort, ['model', 'name', 'newest', 'curated'], true)) {
            $sort = 'model';
        }

        return [
            'search' => trim((string) $request->query('q', '')),
            'category_id' => max(0, (int) $request->query('category_id', '0')),
            'status' => $status,
            'featured' => (string) $request->query('featured', '') === '1',
            'is_new' => (string) $request->query('is_new', '') === '1',
            'incomplete' => $forceIncomplete ?? ((string) $request->query('incomplete', '') === '1'),
            'sort' => $sort,
            'page' => max(1, (int) $request->query('page', '1')),
        ];
    }

    /**
     * Everything the card faces render from, in bulk.
     *
     * @param array{ids: int[], families: array<int, int[]>} $grid
     * @return array<string, mixed>
     */
    private function cardData(array $grid, string $locale): array
    {
        $ids = array_map('intval', $grid['ids'] ?? []);
        $families = $grid['families'] ?? [];
        $empty = [
            'products' => [], 'payloads' => [], 'cards' => [], 'mediaState' => [],
            'chips' => [], 'finishSpecs' => [], 'covers' => [], 'galleries' => [], 'members' => [], 'reasons' => [],
        ];
        if ($ids === []) {
            return $empty;
        }

        $memberIds = [];
        foreach ($families as $members) {
            $memberIds = [...$memberIds, ...array_map('intval', $members)];
        }
        $allIds = array_values(array_unique([...$ids, ...$memberIds]));

        $models = Product::query()->whereIn('id', $allIds)->get();
        Product::eagerLoad($models, ['translations', 'variants', 'specs', 'card_features', 'dimensions', 'media', 'category']);
        $byId = [];
        foreach ($models as $model) {
            $byId[(int) $model->id] = $model;
        }

        $products = [];
        $payloads = [];
        $cards = [];
        $mediaState = [];
        $covers = [];
        $galleries = [];
        $membersOut = [];
        foreach ($ids as $id) {
            if (!isset($byId[$id])) {
                continue;
            }
            $product = $byId[$id];
            $products[] = $product;
            $payloads[$id] = $product->translate($locale);
            $cards[$id] = ProductCardPresenter::make($payloads[$id], $locale);
            $mediaState[$id] = ProductVisibility::media($product);
            $covers[$id] = $this->coverOf($product);
            $galleries[$id] = [];
            foreach ($product->mediaBySection()['photos'] ?? [] as $photo) {
                $galleries[$id][] = [
                    'attachment_id' => (int) ($photo['attachment_id'] ?? 0),
                    'path' => (string) ($photo['path'] ?? ''),
                ];
            }

            if (isset($families[$id])) {
                $list = [];
                foreach ($families[$id] as $memberId) {
                    $memberId = (int) $memberId;
                    if (!isset($byId[$memberId])) {
                        continue;
                    }
                    $memberPayload = $byId[$memberId]->translate($locale);
                    $memberCard = ProductCardPresenter::make($memberPayload, $locale);
                    $finish = $memberCard['finishes'][0] ?? null;
                    $list[] = [
                        'id' => $memberId,
                        'model_code' => (string) ($memberPayload['model_code'] ?? ''),
                        'name' => (string) ($memberPayload['name'] ?? ''),
                        'finish_key' => (string) ($finish['key'] ?? 'neutral'),
                        'finish_label' => (string) ($finish['label'] ?? ''),
                    ];
                }
                if (count($list) >= 2) {
                    $membersOut[$id] = $list;
                }
            }
        }

        return [
            'products' => $products,
            'payloads' => $payloads,
            'cards' => $cards,
            'mediaState' => $mediaState,
            'chips' => $this->products->cardChipsBulk($ids, $locale),
            'finishSpecs' => $this->products->finishSpecsBulk($ids),
            'covers' => $covers,
            'galleries' => $galleries,
            'members' => $membersOut,
            'reasons' => $this->products->incompleteReasons($ids),
        ];
    }

    /** The card cover = the main photo attachment (for the lightbox). */
    private function coverOf(Product $product): ?array
    {
        $photos = $product->mediaBySection()['photos'] ?? [];
        if ($photos === []) {
            return null;
        }

        $cover = null;
        foreach ($photos as $row) {
            if ((int) ($row['is_primary'] ?? 0) === 1) {
                $cover = $row;
                break;
            }
        }
        $cover ??= $photos[0];

        return [
            'attachment_id' => (int) ($cover['attachment_id'] ?? 0),
            'path' => (string) ($cover['path'] ?? ''),
            'section' => 'photos',
        ];
    }

    /** @return array<int, string> category id => "Level 1 › Level 2 › Level 3" */
    private function categoryPaths(): array
    {
        $paths = [];
        foreach ($this->flatCategories() as $row) {
            $paths[(int) $row['id']] = str_replace(' → ', ' › ', (string) ($row['path'] ?? $row['name']));
        }

        return $paths;
    }

    /** Nested category tree for the browser columns and the picker. */
    private function categoryTree(): array
    {
        $byParent = [];
        foreach ($this->flatCategories() as $row) {
            $byParent[(int) ($row['parent_id'] ?? 0)][] = $row;
        }

        $build = static function (int $parentId) use (&$build, $byParent): array {
            $out = [];
            foreach ($byParent[$parentId] ?? [] as $row) {
                $id = (int) $row['id'];
                $out[] = [
                    'id' => $id,
                    'name' => (string) $row['name'],
                    'status' => (string) $row['status'],
                    'count' => (int) ($row['subtree_products'] ?? 0),
                    'children' => $build($id),
                ];
            }

            return $out;
        };

        return $build(0);
    }

    /** @return array<int, array<string, mixed>> */
    private function flatCategories(): array
    {
        return $this->flatCategoriesCache ??= app(\App\Services\CategoryAdminService::class)->flatTree('en', 'en');
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
            'collections' => Product::query()->connection()->select("SELECT c.id, c.slug, COALESCE(ct.name, c.slug) AS display_name FROM collections c LEFT JOIN collection_translations ct ON ct.collection_id = c.id AND ct.locale = 'en' WHERE c.deleted_at IS NULL ORDER BY c.sort_order ASC, c.id ASC"),
            'brands' => Product::query()->connection()->select('SELECT id, name FROM brands WHERE deleted_at IS NULL ORDER BY id ASC'),
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
}
