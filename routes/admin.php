<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CollectionController;
use App\Http\Controllers\Admin\PasswordController;
use App\Http\Controllers\DashboardController;
use Core\Http\Router;

return function (Router $router): void {
    $router->group([
        'prefix' => 'admin',
        'middleware' => ['web', 'auth'],
        'name' => 'admin.',
    ], function (Router $router): void {
        $router->get('/', [DashboardController::class, 'index'])->name('dashboard');

        /* Single-administrator account: change own password. */
        $router->get('/password', [PasswordController::class, 'edit'])->name('password.edit');
        $router->post('/password', [PasswordController::class, 'update'])->name('password.update');

        /* ----- Category tree (3 levels) ----- */
        $router->get('/categories', [CategoryController::class, 'index'])
            ->middleware('permission:categories.view')->name('categories.index');
        $router->get('/categories/create', [CategoryController::class, 'create'])
            ->middleware('permission:categories.manage')->name('categories.create');
        $router->post('/categories', [CategoryController::class, 'store'])
            ->middleware('permission:categories.manage')->name('categories.store');
        $router->get('/categories/{id}/edit', [CategoryController::class, 'edit'])
            ->middleware('permission:categories.manage')->where('id', '\d+')->name('categories.edit');
        $router->post('/categories/{id}', [CategoryController::class, 'update'])
            ->middleware('permission:categories.manage')->where('id', '\d+')->name('categories.update');
        $router->post('/categories/{id}/delete', [CategoryController::class, 'destroy'])
            ->middleware('permission:categories.manage')->where('id', '\d+')->name('categories.destroy');
        $router->post('/categories/{id}/move', [CategoryController::class, 'move'])
            ->middleware('permission:categories.manage')->where('id', '\d+')->name('categories.move');
        $router->post('/categories/{id}/status', [CategoryController::class, 'toggleStatus'])
            ->middleware('permission:categories.manage')->where('id', '\d+')->name('categories.status');

        /* ----- Collections ----- */
        $router->get('/collections', [CollectionController::class, 'index'])
            ->middleware('permission:categories.view')->name('collections.index');
        $router->get('/collections/create', [CollectionController::class, 'create'])
            ->middleware('permission:categories.manage')->name('collections.create');
        $router->post('/collections', [CollectionController::class, 'store'])
            ->middleware('permission:categories.manage')->name('collections.store');
        $router->get('/collections/{id}/edit', [CollectionController::class, 'edit'])
            ->middleware('permission:categories.manage')->where('id', '\d+')->name('collections.edit');
        $router->post('/collections/{id}', [CollectionController::class, 'update'])
            ->middleware('permission:categories.manage')->where('id', '\d+')->name('collections.update');
        $router->post('/collections/{id}/delete', [CollectionController::class, 'destroy'])
            ->middleware('permission:categories.manage')->where('id', '\d+')->name('collections.destroy');

        /* ----- Brands ----- */
        $router->get('/brands', [BrandController::class, 'index'])
            ->middleware('permission:categories.view')->name('brands.index');
        $router->get('/brands/create', [BrandController::class, 'create'])
            ->middleware('permission:categories.manage')->name('brands.create');
        $router->post('/brands', [BrandController::class, 'store'])
            ->middleware('permission:categories.manage')->name('brands.store');
        $router->get('/brands/{id}/edit', [BrandController::class, 'edit'])
            ->middleware('permission:categories.manage')->where('id', '\d+')->name('brands.edit');
        $router->post('/brands/{id}', [BrandController::class, 'update'])
            ->middleware('permission:categories.manage')->where('id', '\d+')->name('brands.update');
        $router->post('/brands/{id}/delete', [BrandController::class, 'destroy'])
            ->middleware('permission:categories.manage')->where('id', '\d+')->name('brands.destroy');
    });
};
