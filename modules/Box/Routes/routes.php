<?php

declare(strict_types=1);

use Core\Http\Router;
use Modules\Box\Controllers\BoxController;

return function (Router $router): void {
    if (!feature('box', true) || !(bool) config('box.enabled', true)) {
        return;
    }

    $router->group(['middleware' => 'web'], function (Router $router): void {
        $router->localized('GET', 'box.index', [BoxController::class, 'index'])
            ->name('box.index');

        $router->localized('GET', 'box.claim', [BoxController::class, 'claim'])
            ->where('token', '[A-Za-z0-9]{16,64}')
            ->name('box.claim');

        $router->localized('POST', 'box.add', [BoxController::class, 'add'])
            ->name('box.add');

        $router->localized('POST', 'box.remove', [BoxController::class, 'remove'])
            ->name('box.remove');

        $router->localized('POST', 'box.clear', [BoxController::class, 'clear'])
            ->name('box.clear');

        $router->localized('POST', 'box.send', [BoxController::class, 'send'])
            ->name('box.send');
    });
};
