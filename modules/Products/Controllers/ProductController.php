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
        $searchQuery = (string) $request->query('q', '');

        $paginator = $this->products->paginate([
            'locale' => $locale,
            'status' => 'active',
            'category_slug' => $categorySlug,
            'search' => $searchQuery,
        ], (int) $request->query('page', '1'), 12);

        $connection = \Modules\Products\Models\Product::query()->connection();
        $categories = $connection->select(
            "SELECT c.id, c.slug, c.image, ct.name FROM categories c
             LEFT JOIN category_translations ct ON ct.category_id = c.id AND ct.locale = ?
             WHERE c.deleted_at IS NULL AND c.status = 'active'
             ORDER BY c.id ASC",
            [$locale]
        );

        $this->seo->setTitle(trans('products.title'));

        return $this->view('products::index', [
            'title' => trans('products.title'),
            'paginator' => $paginator,
            'categories' => $categories,
            'activeCategory' => $categorySlug,
            'searchQuery' => $searchQuery,
            'locale' => $locale,
            'seo' => $this->seo,
        ]);
    }

    public function show(string $slug): Response
    {
        $locale = $this->translator->getLocale();
        $product = $this->products->findTranslatedBySlug($slug, $locale);

        $this->seo->setTitle((string) ($product['name'] ?? ''));
        $this->seo->setDescription((string) ($product['short_description'] ?? ''));
        $this->seo->setFromEntity($product);

        return $this->view('products::show', [
            'title' => $product['name'] ?? '',
            'product' => $product,
            'locale' => $locale,
            'seo' => $this->seo,
        ]);
    }

    /* ------------------------------------------------------- admin panel */

    public function adminIndex(Request $request): Response
    {
        $paginator = $this->products->paginate([
            'search' => (string) $request->query('q', ''),
        ], (int) $request->query('page', '1'), 10);

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

        return $this->redirect(route('admin.products.edit', ['id' => $product->id]))
            ->with('_success', trans('common.saved'));
    }

    public function adminEdit(int $id): Response
    {
        $product = $this->products->find($id);

        return $this->view('products::Admin.form', [
            'title' => trans('common.edit_product'),
            'product' => $product,
            'translations' => $product->translations(),
        ]);
    }

    public function adminUpdate(Request $request, int $id): RedirectResponse
    {
        $data = (new UpdateProductRequest())->forProduct($id)->handle($request);

        $this->products->update($id, $this->coreFields($data), $this->translationsFromForm($data));

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
            'sku' => $data['sku'],
            'slug' => $data['slug'] ?? '',
            'price' => $data['price'],
            'category_id' => $data['category_id'] ?? null,
            'status' => $data['status'],
            'seo' => [
                'title' => $data['seo_title'] ?? '',
                'description' => $data['seo_description'] ?? '',
            ],
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
                    'short_description' => mb_substr((string) $description, 0, 160),
                ];
            }
        }

        return $translations;
    }
}
