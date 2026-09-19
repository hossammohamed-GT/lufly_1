<?php

declare(strict_types=1);

namespace Modules\Products\Controllers\Api;

use App\Http\Controllers\Controller;
use Core\Http\ApiResponse;
use Core\Http\JsonResponse;
use Core\Http\Request;
use Core\Localization\Translator;
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
        $paginator = $this->products->paginate([
            'locale' => (string) $request->query('locale', $this->translator->getLocale()),
            'status' => (string) $request->query('status', 'active'),
            'search' => (string) $request->query('q', ''),
        ], (int) $request->query('page', '1'), (int) $request->query('per_page', '10'));

        $items = array_map(
            fn ($product) => $product->translate((string) $request->query('locale', $this->translator->getLocale())),
            $paginator->items(),
        );

        return ApiResponse::paginated($items, $paginator);
    }

    public function search(Request $request): JsonResponse
    {
        $locale = (string) $request->query('locale', $this->translator->getLocale());
        $query = trim((string) $request->query('q', ''));
        $limit = max(1, (int) $request->query('limit', '8'));
        $category = trim((string) $request->query('category', ''));
        if (!preg_match('/^[a-z0-9-]{1,150}$/', $category)) {
            $category = '';
        }

        if ($query === '') {
            return ApiResponse::success([]);
        }

        $items = $this->products->quickSearch($query, $locale, $limit, $category !== '' ? $category : null);

        return ApiResponse::success($items);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $product = $this->products->find($id);
        $locale = (string) $request->query('locale', $this->translator->getLocale());

        return ApiResponse::success([
            'product' => $product->translate($locale),
            'translations' => $product->translations(),
        ]);
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
