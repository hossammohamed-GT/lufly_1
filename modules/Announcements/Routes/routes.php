<?php

declare(strict_types=1);

use Core\Http\Router;
use Modules\Announcements\Controllers\AnnouncementController;

return function (Router $router): void {
    $router->group([
        'prefix' => 'admin/announcements',
        'middleware' => ['web', 'auth'],
        'name' => 'admin.announcements.',
    ], function (Router $router): void {
        $router->get('/', [AnnouncementController::class, 'index'])
            ->middleware('permission:announcements.view')
            ->name('index');

        $router->get('/create', [AnnouncementController::class, 'create'])
            ->middleware('permission:announcements.manage')
            ->name('create');

        $router->post('/', [AnnouncementController::class, 'store'])
            ->middleware('permission:announcements.manage')
            ->name('store');

        $router->get('/{id}/edit', [AnnouncementController::class, 'edit'])
            ->middleware('permission:announcements.manage')
            ->where('id', '\d+')
            ->name('edit');

        $router->post('/{id}', [AnnouncementController::class, 'update'])
            ->middleware('permission:announcements.manage')
            ->where('id', '\d+')
            ->name('update');

        $router->post('/{id}/toggle', [AnnouncementController::class, 'toggle'])
            ->middleware('permission:announcements.manage')
            ->where('id', '\d+')
            ->name('toggle');

        $router->post('/{id}/delete', [AnnouncementController::class, 'destroy'])
            ->middleware('permission:announcements.manage')
            ->where('id', '\d+')
            ->name('destroy');
    });
};
