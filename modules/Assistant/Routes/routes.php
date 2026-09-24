<?php

declare(strict_types=1);

use Core\Http\Router;
use Modules\Assistant\Controllers\AssistantController;

return function (Router $router): void {
    if (!feature('ai', true) || !(bool) config('assistant.enabled', true)) {
        return;
    }

    $router->group(['middleware' => 'web'], function (Router $router): void {
        $router->localized('POST', 'assistant.ask', [AssistantController::class, 'ask'])
            ->name('assistant.ask');
    });
};
