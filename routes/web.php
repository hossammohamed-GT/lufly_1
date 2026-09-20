<?php

declare(strict_types=1);

use App\Http\Controllers\HomeController;
use App\Http\Controllers\SeoAssetsController;
use Core\Http\Router;

return function (Router $router): void {
    $router->get('/', [HomeController::class, 'root'])->name('root');

    /* Machine-facing SEO assets (no session, no locale prefix). Static
       copies must NOT exist in public/ or Apache would shadow these. */
    $router->get('/robots.txt', [SeoAssetsController::class, 'robots'])->name('seo.robots');
    $router->get('/sitemap.xml', [SeoAssetsController::class, 'sitemapIndex'])->name('seo.sitemap');
    $router->get('/sitemaps/pages.xml', [SeoAssetsController::class, 'sitemapPages'])->name('seo.sitemap.pages');
    $router->get('/sitemaps/products.xml', [SeoAssetsController::class, 'sitemapProducts'])->name('seo.sitemap.products');
    $router->get('/sitemaps/images.xml', [SeoAssetsController::class, 'sitemapImages'])->name('seo.sitemap.images');
    $router->get('/og-image', [SeoAssetsController::class, 'ogImage'])->name('seo.ogimage');

    $router->group(['middleware' => 'web'], function (Router $router): void {
        $router->localized('GET', 'home', [HomeController::class, 'index'])->name('home');

        // Contact page: /en/contact, /tr/iletisim, /cs/kontakt
        $router->localized('GET', 'contact', [HomeController::class, 'contact'], 'contact')
            ->name('contact');
    });
};
