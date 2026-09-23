<?php

declare(strict_types=1);

use Core\Http\Router;
use Modules\Planner\Controllers\PlannerController;

return function (Router $router): void {
    if (!feature('planner', true) || !(bool) config('planner.enabled', true)) {
        return;
    }

    /* Localized storefront routes:
       /en/planner · /tr/banyo-planlayici · /cs/planovac-koupelny
       (see resources/lang/<locale>/routes.php) */
    $router->group(['middleware' => 'web'], function (Router $router): void {
        $router->localized('GET', 'planner.index', [PlannerController::class, 'index'])
            ->name('planner.index');

        /* one answer per tap: size / wet area / look → next question or the plan */
        $router->localized('POST', 'planner.step', [PlannerController::class, 'step'])
            ->name('planner.step');

        /* "does the piece I was looking at fit my plan?" (text only, no images) */
        $router->localized('POST', 'planner.fit', [PlannerController::class, 'fit'])
            ->name('planner.fit');

        /* the optional picture of the finished room */
        $router->localized('POST', 'planner.render', [PlannerController::class, 'render'])
            ->name('planner.render');

        /* hand the plan to the LUFLY team so they can source the pieces */
        $router->localized('POST', 'planner.send', [PlannerController::class, 'send'])
            ->name('planner.send');
    });
};
