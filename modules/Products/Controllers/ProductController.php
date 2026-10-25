<?php

declare(strict_types=1);

namespace Modules\Products\Controllers;

use App\Http\Controllers\Controller;
use App\Services\CategoryService;
use App\Services\SEOService;
use Core\Http\RedirectResponse;
use Core\Http\Request;
use Core\Http\Response;
use Core\Localization\Translator;
use Modules\Products\Requests\StoreProductRequest;
use Modules\Products\Requests\UpdateProductRequest;
use Modules\Products\Services\ProductMediaService;
use Modules\Products\Services\ProductService;
use Modules\Products\Services\ProductVisibility;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $products,
        private readonly ProductMediaService $media,
        private readonly SEOService $seo,
        private readonly CategoryService $categories,
        private readonly Translator $translator,
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
        $product = $this->products->findTranslatedBySlug($slug, $locale);

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

        return $this->view('products::show', [
            'title' => $productName,
            'product' => $product,
            'categoryBreadcrumbs' => $categoryBreadcrumbs,
            'locale' => $locale,
            'seo' => $this->seo,
        ]);
    }

    /* ------------------------------------------------------- admin panel */

    public function adminIndex(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));
        $page = max(1, (int) $request->query('page', '1'));
        $perPage = 15;

        $paginator = $this->products->paginate([
            'search' => $search,
            'locale' => $this->translator->getLocale(),
        ], $page, $perPage);

        return $this->view('products::Admin.index', [
            'title' => trans('common.products'),
            'paginator' => $paginator,
        ]);
    }

    public function adminCreate(): Response
    {
        return $this->view('products::Admin.form', [
            'title' => trans('common.create_product'),
            'product' => null,
            'mediaSections' => [],
            'translations' => [],
        ]);
    }

    public function adminStore(Request $request): RedirectResponse
    {
        $data = (new StoreProductRequest())->handle($request);

        $product = $this->products->create(
            $this->coreFields($data),
            $this->translationsFromForm($data),
        );

        if ($request->hasFile('image')) {
            $this->attachPrimaryImage((int) $product->id, $request->file('image'));
        }

        return $this->redirect(route('admin.products.edit', ['id' => $product->id]))
            ->with('_success', trans('common.saved'));
    }

    public function adminEdit(int $id): Response
    {
        $product = $this->products->find($id);

        return $this->view('products::Admin.form', [
            'title' => trans('common.edit_product'),
            'product' => $product,
            'mediaSections' => $product !== null ? $product->mediaBySection() : [],
            'translations' => $product !== null ? $product->translations() : [],
        ]);
    }

    public function adminUpdate(Request $request, int $id): RedirectResponse
    {
        $data = (new UpdateProductRequest())->forProduct($id)->handle($request);

        $this->products->update($id, $this->coreFields($data), $this->translationsFromForm($data));

        if ($request->hasFile('image')) {
            $this->attachPrimaryImage($id, $request->file('image'));
        }

        return $this->redirect(route('admin.products.edit', ['id' => $id]))
            ->with('_success', trans('common.saved'));
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
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function coreFields(array $data): array
    {
        return [
            'model_code' => $data['model_code'],
            'slug' => $data['slug'] ?? '',
            'price' => (float) ($data['price'] ?? 0),
            'category_id' => ($data['category_id'] ?? '') !== '' ? (int) $data['category_id'] : null,
            'collection_id' => ($data['collection_id'] ?? '') !== '' ? (int) $data['collection_id'] : null,
            'brand_id' => ($data['brand_id'] ?? '') !== '' ? (int) $data['brand_id'] : null,
            'is_featured' => (string) ($data['is_featured'] ?? '0'),
            'status' => $data['status'],
            'meta_title' => $data['meta_title'] ?? '',
            'meta_description' => $data['meta_description'] ?? '',
        ];
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
     * @param array<string, mixed> $file
     */
    private function attachPrimaryImage(int $productId, array $file): void
    {
        $this->media->upload($productId, $file, 'photos', true);
    }
}
