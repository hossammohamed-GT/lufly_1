<?php

declare(strict_types=1);

namespace Modules\Products\Controllers;

use App\Http\Controllers\Controller;
use App\Services\SEOService;
use Core\Http\RedirectResponse;
use Core\Http\Request;
use Core\Http\Response;
use Core\Localization\Translator;
use Modules\Products\Requests\StoreProductRequest;
use Modules\Products\Requests\UpdateProductRequest;
use Modules\Products\Services\ProductService;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $products,
        private readonly SEOService $seo,
        private readonly Translator $translator,
    ) {
    }

    public function index(Request $request): Response
    {
        $locale = $this->translator->getLocale();
        $categorySlug = (string) $request->query('category', '');
        $categoryId = (int) $request->query('category_id', '0');
        $searchQuery = (string) $request->query('q', '');
        $sort = (string) $request->query('sort', 'newest');
        if (!in_array($sort, ['newest', 'name', 'model'], true)) {
            $sort = 'newest';
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

        $connection = \Modules\Products\Models\Product::query()->connection();
        $categories = $connection->select(
            "SELECT c.id, c.slug, c.image, COALESCE(ct.name, ctf.name) AS name FROM categories c
             LEFT JOIN category_translations ct ON ct.category_id = c.id AND ct.locale = ?
             LEFT JOIN category_translations ctf ON ctf.category_id = c.id AND ctf.locale = ?
             WHERE c.deleted_at IS NULL AND c.status = 'active'
             ORDER BY c.id ASC",
            [$locale, $fallback]
        );

        /* per-category counts for the coded category rail */
        $categoryCounts = [];
        foreach ($connection->select(
            "SELECT c.slug AS slug, COUNT(p.id) AS n
             FROM categories c
             LEFT JOIN products p ON p.category_id = c.id AND p.deleted_at IS NULL AND p.status = 'active'
             WHERE c.deleted_at IS NULL AND c.status = 'active'
             GROUP BY c.id, c.slug",
            []
        ) as $row) {
            $categoryCounts[(string) $row['slug']] = (int) $row['n'];
        }
        $categoryCounts[''] = array_sum($categoryCounts);

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
            || $sort !== 'newest' || (int) $request->query('page', '1') > 1;
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
        $categorySlug = '';
        if (!empty($product['category_id'])) {
            $connection = \Modules\Products\Models\Product::query()->connection();
            $row = $connection->selectOne(
                'SELECT slug FROM categories WHERE id = ? AND deleted_at IS NULL',
                [(int) $product['category_id']],
            );
            $categorySlug = (string) ($row['slug'] ?? '');
        }
        $product['category_slug'] = $categorySlug;

        $productName = (string) ($product['name'] ?? 'LUFLY Architectural Fixture');
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
            'subtitle' => $product['category_slug'] !== '' ? 'LUFLY ' . ucfirst((string) $product['category_slug']) : 'LUFLY',
        ]));

        $this->seo->addProductSchema($product, $canonical);
        $this->seo->addWebPageSchema([
            'name' => $productName,
            'description' => $productDesc,
            'url' => $canonical,
            'inLanguage' => $locale,
        ]);
        $this->seo->addBreadcrumb([
            ['name' => 'LUFLY', 'url' => route('home')],
            ['name' => trans('products.title'), 'url' => route('products.index')],
            ['name' => $productName, 'url' => $canonical],
        ]);

        return $this->view('products::show', [
            'title' => $productName,
            'product' => $product,
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
            'primaryImage' => null,
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
        $translated = $product !== null ? $product->translate($this->translator->getLocale()) : [];
        $primaryImage = !empty($translated['image']) ? (string) $translated['image'] : null;

        return $this->view('products::Admin.form', [
            'title' => trans('common.edit_product'),
            'product' => $product,
            'primaryImage' => $primaryImage,
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
     * @param array<string, mixed> $file
     */
    private function attachPrimaryImage(int $productId, array $file): void
    {
        /** @var \App\Services\MediaService $mediaService */
        $mediaService = app(\App\Services\MediaService::class);
        $media = $mediaService->storeFromUpload($file, 'products', auth()->id());

        $connection = \Modules\Products\Models\Product::query()->connection();
        $connection->affect(
            'UPDATE product_media SET is_primary = 0 WHERE product_id = ?',
            [$productId]
        );

        $connection->insert('product_media', [
            'product_id' => $productId,
            'variant_id' => null,
            'media_id' => (int) $media->id,
            'type' => 'main',
            'sort_order' => 1,
            'is_primary' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
