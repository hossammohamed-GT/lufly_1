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

        /* ----- Category tree (3 levels) — everything edits in place ----- */
        $router->get('/categories', [CategoryController::class, 'index'])
            ->middleware('permission:categories.view')->name('categories.index');
        $router->get('/categories/tree', [CategoryController::class, 'treeFragment'])
            ->middleware('permission:categories.view')->name('categories.tree');
        $router->get('/categories/{id}/row', [CategoryController::class, 'row'])
            ->middleware('permission:categories.view')->where('id', '\d+')->name('categories.row');
        $router->get('/categories/{id}/editor', [CategoryController::class, 'editor'])
            ->middleware('permission:categories.view')->where('id', '\d+')->name('categories.editor');
        $router->post('/categories/quick', [CategoryController::class, 'quickStore'])
            ->middleware('permission:categories.manage')->name('categories.quick');
        $router->post('/categories/{id}/inline', [CategoryController::class, 'inline'])
            ->middleware('permission:categories.manage')->where('id', '\d+')->name('categories.inline');
        $router->post('/categories/{id}/section', [CategoryController::class, 'section'])
            ->middleware('permission:categories.manage')->where('id', '\d+')->name('categories.section');
        $router->post('/categories/{id}/image', [CategoryController::class, 'image'])
            ->middleware('permission:categories.manage')->where('id', '\d+')->name('categories.image');
        $router->post('/categories/{id}/move', [CategoryController::class, 'move'])
            ->middleware('permission:categories.manage')->where('id', '\d+')->name('categories.move');
        $router->post('/categories/{id}/delete', [CategoryController::class, 'destroy'])
            ->middleware('permission:categories.manage')->where('id', '\d+')->name('categories.destroy');

        /* ----- Collections — inline-lite ----- */
        $router->get('/collections', [CollectionController::class, 'index'])
            ->middleware('permission:categories.view')->name('collections.index');
        $router->get('/collections/{id}/editor', [CollectionController::class, 'editor'])
            ->middleware('permission:categories.view')->where('id', '\d+')->name('collections.editor');
        $router->post('/collections/quick', [CollectionController::class, 'quickStore'])
            ->middleware('permission:categories.manage')->name('collections.quick');
        $router->post('/collections/{id}/inline', [CollectionController::class, 'inline'])
            ->middleware('permission:categories.manage')->where('id', '\d+')->name('collections.inline');
        $router->post('/collections/{id}/section', [CollectionController::class, 'section'])
            ->middleware('permission:categories.manage')->where('id', '\d+')->name('collections.section');
        $router->post('/collections/{id}/image', [CollectionController::class, 'image'])
            ->middleware('permission:categories.manage')->where('id', '\d+')->name('collections.image');
        $router->post('/collections/{id}/delete', [CollectionController::class, 'destroy'])
            ->middleware('permission:categories.manage')->where('id', '\d+')->name('collections.destroy');

        /* ----- Brands — inline-lite ----- */
        $router->get('/brands', [BrandController::class, 'index'])
            ->middleware('permission:categories.view')->name('brands.index');
        $router->get('/brands/{id}/editor', [BrandController::class, 'editor'])
            ->middleware('permission:categories.view')->where('id', '\d+')->name('brands.editor');
        $router->post('/brands/quick', [BrandController::class, 'quickStore'])
            ->middleware('permission:categories.manage')->name('brands.quick');
        $router->post('/brands/{id}/inline', [BrandController::class, 'inline'])
            ->middleware('permission:categories.manage')->where('id', '\d+')->name('brands.inline');
        $router->post('/brands/{id}/section', [BrandController::class, 'section'])
            ->middleware('permission:categories.manage')->where('id', '\d+')->name('brands.section');
        $router->post('/brands/{id}/image', [BrandController::class, 'image'])
            ->middleware('permission:categories.manage')->where('id', '\d+')->name('brands.image');
        $router->post('/brands/{id}/delete', [BrandController::class, 'destroy'])
            ->middleware('permission:categories.manage')->where('id', '\d+')->name('brands.destroy');
    });
};
