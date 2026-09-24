<?php

declare(strict_types=1);

use Core\Http\Router;
use Modules\Planner\Controllers\PlannerController;

return function (Router $router): void {
    if (!feature('planner', true) || !(bool) config('planner.enabled', true)) {
        return;
    }

    $router->group(['middleware' => 'web'], function (Router $router): void {
        $router->localized('GET', 'planner.index', [PlannerController::class, 'index'])
            ->name('planner.index');

        $router->localized('POST', 'planner.step', [PlannerController::class, 'step'])
            ->name('planner.step');

        $router->localized('POST', 'planner.fit', [PlannerController::class, 'fit'])
            ->name('planner.fit');

        $router->localized('POST', 'planner.render', [PlannerController::class, 'render'])
            ->name('planner.render');

        $router->localized('POST', 'planner.send', [PlannerController::class, 'send'])
            ->name('planner.send');
    });
};
