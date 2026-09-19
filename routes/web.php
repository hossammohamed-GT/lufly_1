<?php

declare(strict_types=1);

use App\Http\Controllers\HomeController;
use Core\Http\Router;

return function (Router $router): void {
    $router->get('/', [HomeController::class, 'root'])->name('root');

    $router->group(['middleware' => 'web'], function (Router $router): void {
        $router->localized('GET', 'home', [HomeController::class, 'index'])->name('home');

        // Contact page: /en/contact, /tr/iletisim, /cs/kontakt
        $router->localized('GET', 'contact', [HomeController::class, 'contact'], 'contact')
            ->name('contact');
    });
};
