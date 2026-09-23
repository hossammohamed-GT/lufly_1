<?php

declare(strict_types=1);

use Core\Http\Router;
use Modules\Products\Controllers\Api\ProductApiController;
use Modules\Products\Controllers\ProductController;
use Modules\Products\Controllers\ProductMediaController;

return function (Router $router): void {
    // Localized public storefront routes: /en/products, /tr/urunler, /cs/produkty ...
    $router->group(['middleware' => 'web'], function (Router $router): void {
        $router->localized('GET', 'products.index', [ProductController::class, 'index'])
            ->name('products.index');

        $router->localized('GET', 'products.show', [ProductController::class, 'show'])
            ->where('slug', '[a-z0-9\-]+')
            ->name('products.show');
    });

    // Admin panel routes.
    $router->group([
        'prefix' => 'admin/products',
        'middleware' => ['web', 'auth'],
        'name' => 'admin.products.',
    ], function (Router $router): void {
        $router->get('/', [ProductController::class, 'adminIndex'])
            ->middleware('permission:products.view')
            ->name('index');

        $router->get('/create', [ProductController::class, 'adminCreate'])
            ->middleware('permission:products.manage')
            ->name('create');

        $router->post('/', [ProductController::class, 'adminStore'])
            ->middleware('permission:products.manage')
            ->name('store');

        $router->get('/{id}/edit', [ProductController::class, 'adminEdit'])
            ->middleware('permission:products.manage')
            ->where('id', '\d+')
            ->name('edit');

        $router->post('/{id}', [ProductController::class, 'adminUpdate'])
            ->middleware('permission:products.manage')
            ->where('id', '\d+')
            ->name('update');

        $router->post('/{id}/delete', [ProductController::class, 'adminDestroy'])
            ->middleware('permission:products.manage')
            ->where('id', '\d+')
            ->name('destroy');

        /* product images: photos / technical drawings / installed shots */
        $router->post('/{id}/media', [ProductMediaController::class, 'store'])
            ->middleware('permission:products.manage')
            ->where('id', '\d+')
            ->name('media.store');

        $router->post('/{id}/media/{attachmentId}/section', [ProductMediaController::class, 'move'])
            ->middleware('permission:products.manage')
            ->where('id', '\d+')
            ->where('attachmentId', '\d+')
            ->name('media.move');

        $router->post('/{id}/media/{attachmentId}/primary', [ProductMediaController::class, 'primary'])
            ->middleware('permission:products.manage')
            ->where('id', '\d+')
            ->where('attachmentId', '\d+')
            ->name('media.primary');

        $router->post('/{id}/media/{attachmentId}/order', [ProductMediaController::class, 'order'])
            ->middleware('permission:products.manage')
            ->where('id', '\d+')
            ->where('attachmentId', '\d+')
            ->name('media.order');

        $router->post('/{id}/media/{attachmentId}/delete', [ProductMediaController::class, 'destroy'])
            ->middleware('permission:products.manage')
            ->where('id', '\d+')
            ->where('attachmentId', '\d+')
            ->name('media.destroy');
    });

    // Public + protected REST API.
    $router->group(['prefix' => 'api/products', 'middleware' => 'api'], function (Router $router): void {
        $router->get('/', [ProductApiController::class, 'index'])
            ->name('api.products.index')
            ->doc('List products with pagination, search and locale.', [], [
                'success' => true,
                'data' => [['id' => 1, 'name' => 'Sample product', 'price' => '19.90']],
                'meta' => ['page' => 1, 'per_page' => 10, 'total' => 1, 'last_page' => 1],
            ]);

        $router->get('/search', [ProductApiController::class, 'search'])
            ->name('api.products.search')
            ->doc('Instant product search by SKU or translated product name.', [], [
                'success' => true,
                'data' => [['id' => 1, 'sku' => 'SKU-1', 'name' => 'Silia Smart WC']],
            ]);

        $router->get('/{id}', [ProductApiController::class, 'show'])
            ->where('id', '\d+')
            ->name('api.products.show')
            ->doc('Fetch one product with all its translations.', [], [
                'success' => true,
                'data' => ['product' => ['id' => 1, 'name' => 'Sample product']],
            ]);

        $router->post('/', [ProductApiController::class, 'store'])
            ->middleware(['auth', 'permission:products.manage'])
            ->name('api.products.store')
            ->doc('Create a product with translations.', [
                'sku' => 'required|string|max:100|unique:products,sku',
                'price' => 'required|numeric|min:0',
                'status' => 'required|in:active,inactive,draft',
                'translations' => 'required|array',
            ], [
                'success' => true,
                'message' => 'Saved.',
                'data' => ['product' => ['id' => 2, 'sku' => 'SKU-2']],
            ]);

        $router->put('/{id}', [ProductApiController::class, 'update'])
            ->middleware(['auth', 'permission:products.manage'])
            ->where('id', '\d+')
            ->name('api.products.update')
            ->doc('Update a product and/or its translations.', [
                'sku' => 'nullable|string|max:100',
                'price' => 'nullable|numeric|min:0',
                'status' => 'nullable|in:active,inactive,draft',
                'translations' => 'nullable|array',
            ], [
                'success' => true,
                'message' => 'Saved.',
                'data' => ['product' => ['id' => 1, 'sku' => 'SKU-1']],
            ]);

        $router->delete('/{id}', [ProductApiController::class, 'destroy'])
            ->middleware(['auth', 'permission:products.manage'])
            ->where('id', '\d+')
            ->name('api.products.destroy')
            ->doc('Soft-delete a product.', [], [
                'success' => true,
                'message' => 'Deleted.',
                'data' => new stdClass(),
            ]);
    });
};
