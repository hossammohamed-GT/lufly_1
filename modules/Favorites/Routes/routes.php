<?php

declare(strict_types=1);

use Core\Http\Router;
use Modules\Favorites\Controllers\FavoriteController;

return function (Router $router): void {
    if (!feature('favorites', true)) {
        return;
    }

    $router->group(['middleware' => 'web'], function (Router $router): void {
        $router->localized('GET', 'favorites.index', [FavoriteController::class, 'index'])
            ->name('favorites.index');

        $router->localized('GET', 'favorites.claim', [FavoriteController::class, 'claim'])
            ->where('token', '[A-Za-z0-9]{16,64}')
            ->name('favorites.claim');

        $router->localized('POST', 'favorites.toggle', [FavoriteController::class, 'toggle'])
            ->name('favorites.toggle');

        $router->localized('POST', 'favorites.email', [FavoriteController::class, 'email'])
            ->name('favorites.email');

        $router->localized('POST', 'favorites.clear', [FavoriteController::class, 'clear'])
            ->name('favorites.clear');
    });
};
