<?php

declare(strict_types=1);

use Core\Http\Router;
use Modules\Assistant\Controllers\AssistantController;

return function (Router $router): void {
    if (!feature('ai', true) || !(bool) config('assistant.enabled', true)) {
        return;
    }

    /* Localized storefront route:
       /en/assistant/find · /tr/asistan/ara · /cs/asistent/najdi
       (see resources/lang/<locale>/routes.php — a missing key there would
       render the route name itself as the URL) */
    $router->group(['middleware' => 'web'], function (Router $router): void {
        /* one question: a description, a photo, or both */
        $router->localized('POST', 'assistant.ask', [AssistantController::class, 'ask'])
            ->name('assistant.ask');

        /* the gate: the visitor left an address before the first question, so
           the chat can say "saved" honestly instead of only tucking the address
           into the browser and hoping the first question arrives */
        $router->localized('POST', 'assistant/email', [AssistantController::class, 'email'])
            ->name('assistant.email');
    });
};
