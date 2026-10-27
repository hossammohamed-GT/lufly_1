<?php

declare(strict_types=1);

namespace Modules\Products\Controllers\Api;

use App\Http\Controllers\Controller;
use Core\Http\ApiResponse;
use Core\Http\JsonResponse;
use Core\Http\Request;
use Core\Localization\Translator;
use Modules\Products\Services\ProductCardPresenter;
use Modules\Products\Services\ProductColorFamilyService;
use Modules\Products\Services\ProductService;

class ProductApiController extends Controller
{
    public function __construct(
        private readonly ProductService $products,
        private readonly Translator $translator,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $canManage = auth()->check() && (auth()->userCan('products.view') || auth()->userCan('products.manage'));
        $allowedPublicStatuses = ['active', 'coming_soon'];

        $requestedStatus = (string) $request->query('status', 'active');
        if (!$canManage) {
            $status = in_array($requestedStatus, $allowedPublicStatuses, true) ? $requestedStatus : 'active';
        } else {
            $status = $requestedStatus;
        }

        $page = max(1, (int) $request->query('page', '1'));
        $rawPerPage = (int) $request->query('per_page', '10');
        $perPage = min(100, max(1, $rawPerPage));

        $locale = (string) $request->query('locale', $this->translator->getLocale());
        if ($this->translator->isSupported($locale)) {
            $this->translator->setLocale($locale);
        }
        $paginator = $this->products->paginate([
            'locale' => $locale,
            'status' => $status,
            'search' => (string) $request->query('q', ''),
            'require_media' => !$canManage,
        ], $page, $perPage);

        $colorFamilies = !$canManage ? app(ProductColorFamilyService::class) : null;
        $items = array_map(
            static function ($product) use ($locale, $colorFamilies): array {
                $translated = $product->translate($locale);

                return $colorFamilies !== null
                    ? $colorFamilies->attach($translated, $locale)
                    : $translated;
            },
            $paginator->items(),
        );

        return ApiResponse::paginated($items, $paginator);
    }

    public function search(Request $request): JsonResponse
    {
        $locale = (string) $request->query('locale', $this->translator->getLocale());
        if ($this->translator->isSupported($locale)) {
            $this->translator->setLocale($locale);
        }
        $query = trim((string) $request->query('q', ''));
        $limit = min(50, max(1, (int) $request->query('limit', '8')));
        $category = trim((string) $request->query('category', ''));
        if (!preg_match('/^[a-z0-9.-]{1,150}$/', $category)) {
            $category = '';
        }

        if ($query === '') {
            return ApiResponse::success([]);
        }

        $items = $this->products->quickSearch($query, $locale, $limit, $category !== '' ? $category : null);

        /* Live-search cards carry the same real summary and visitor state as
           their server-rendered siblings. Both lists are fetched only once. */
        $savedIds = [];
        if (feature('favorites', true) && class_exists(\Modules\Favorites\Services\FavoriteService::class)) {
            $savedIds = array_flip(app(\Modules\Favorites\Services\FavoriteService::class)->productIds());
        }
        $boxIds = [];
        if (feature('box', true) && class_exists(\Modules\Box\Services\BoxService::class)) {
            $boxIds = array_flip(app(\Modules\Box\Services\BoxService::class)->productIds());
        }

        $colorFamilies = app(ProductColorFamilyService::class);
        $items = array_map(static function (array $item) use ($savedIds, $boxIds, $locale, $colorFamilies): array {
            $id = (int) ($item['id'] ?? 0);
            $slug = (string) ($item['slug'] ?? '');
            $item['url'] = $slug !== '' ? route('products.show', ['slug' => $slug]) : '';
            $item['card'] = ProductCardPresenter::make($item, $locale);
            $item['is_saved'] = isset($savedIds[$id]);
            $item['in_box'] = isset($boxIds[$id]);

            return $colorFamilies->attach($item, $locale, $savedIds, $boxIds);
        }, $items);

        return ApiResponse::success($items);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $canManage = auth()->check() && (auth()->userCan('products.view') || auth()->userCan('products.manage'));
        $product = $canManage ? $this->products->find($id) : $this->products->findPublic($id);
        $locale = (string) $request->query('locale', $this->translator->getLocale());

        $data = [
            'product' => $product->translate($locale),
        ];

        if ($canManage) {
            $data['translations'] = $product->translations();
        }

        return ApiResponse::success($data);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'model_code' => 'required|string|max:100|unique:products,model_code',
            'price' => 'nullable|numeric|min:0',
            'status' => 'required|in:draft,active,hidden,discontinued,coming_soon',
            'slug' => 'nullable|string|max:255',
            'translations' => 'required|array',
        ]);

        $core = [
            'model_code' => $data['model_code'],
            'price' => (float) ($data['price'] ?? 0),
            'status' => $data['status'],
            'slug' => $data['slug'] ?? '',
        ];

        $product = $this->products->create($core, (array) $data['translations']);

        return ApiResponse::success(['product' => $product->toArray()], trans('common.saved'), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'model_code' => 'nullable|string|max:100|unique:products,model_code,' . $id,
            'price' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:draft,active,hidden,discontinued,coming_soon',
            'translations' => 'nullable|array',
        ]);

        $core = array_intersect_key($data, array_flip(['model_code', 'price', 'status', 'slug']));
        $product = $this->products->update($id, $core, (array) ($data['translations'] ?? []));

        return ApiResponse::success(['product' => $product->toArray()], trans('common.saved'));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->products->delete($id);

        return ApiResponse::success(null, trans('common.deleted'));
    }
}
