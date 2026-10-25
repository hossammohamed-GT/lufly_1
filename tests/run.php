<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$failures = [];

$assert = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) {
        $failures[] = $message;
        fwrite(STDERR, "FAIL: {$message}\n");
    } else {
        fwrite(STDOUT, "PASS: {$message}\n");
    }
};

require $root . '/core/Config/Env.php';
use Core\Config\Env;

$assert(Env::parseValue('true') === true, 'Env parses booleans');
$assert(Env::parseValue('value # comment') === 'value', 'Env strips inline comments');
$assert(Env::parseValue('"value # kept"') === 'value # kept', 'Env preserves quoted values');

$example = (string) file_get_contents($root . '/.env.example');
$assert(preg_match('/^APP_KEY=\s*$/m', $example) === 1, 'Example environment contains no shared APP_KEY');

$csrf = (string) file_get_contents($root . '/core/Http/Middleware/CsrfGuard.php');
$assert(!str_contains($csrf, 'permit same-origin AJAX'), 'AJAX requests cannot bypass CSRF token validation');
$assert(str_contains($csrf, 'hash_equals'), 'CSRF tokens use timing-safe comparison');

$security = (string) file_get_contents($root . '/config/security.php')
    . (string) file_get_contents($root . '/core/Http/Middleware/SecurityHeaders.php');
foreach (['Permissions-Policy', 'Content-Security-Policy', 'Strict-Transport-Security'] as $control) {
    $assert(str_contains($security, $control), "Security configuration covers {$control}");
}

$schema = (string) file_get_contents($root . '/app/Services/SEOService.php');
$assert(str_contains($schema, '$price > 0'), 'Product offers require a positive price');
$assert(!str_contains($schema, "'priceCurrency' => 'EUR'"), 'Product schema does not invent a currency');

$rootHtaccess = (string) file_get_contents($root . '/.htaccess');
$assert(!preg_match('/FilesMatch "\\\.\(css.*?immutable/s', $rootHtaccess), 'Unhashed CSS/JS are not cached as immutable');

$catalogView = (string) file_get_contents($root . '/modules/Products/Views/index.php');
$catalogPicker = (string) file_get_contents($root . '/resources/views/components/catalog-category-picker.php');
$catalogDock = (string) file_get_contents($root . '/frontend/products/catalog/catalog-dock.js');
$catalogLiveSearch = (string) file_get_contents($root . '/frontend/js/live-search.js');
$catalogExplorer = (string) file_get_contents($root . '/frontend/components/category-explorer/category-explorer.js');
$catalogCss = (string) file_get_contents($root . '/frontend/products/catalog/catalog.css');
$navbarView = (string) file_get_contents($root . '/resources/views/components/navbar.php');
$navbarJs = (string) file_get_contents($root . '/frontend/components/navbar/navbar.js');
$navbarCss = (string) file_get_contents($root . '/frontend/components/navbar/navbar.css');
$assert(preg_match_all('/\\sdata-livesearch(?:\\s|=)/', $catalogView) === 1 && substr_count($catalogView, 'data-livesearch-input') === 1, 'Catalog keeps exactly one live-search host and input');
$assert(str_contains($catalogView, "component('catalog-category-picker'") && str_contains($catalogView, 'data-catalog-dock-slot') && !str_contains($catalogView, 'data-catalog-dock-category'), 'The original toolbar owns the single sticky category picker');
$assert(str_contains($catalogPicker, 'data-category-view="roots"') && str_contains($catalogPicker, 'data-category-drill') && str_contains($catalogPicker, 'data-category-back'), 'Server-rendered picker exposes roots, nested views, and back navigation');
$assert(!str_contains($catalogDock, 'fetch(') && !str_contains($catalogDock, '.innerHTML'), 'Catalog dock does not become a competing request or rendering controller');
$assert(str_contains($catalogDock, "event('category:select'") && str_contains($catalogExplorer, "explorer.addEventListener('category:select'"), 'Dock categories reuse the atlas activation path');
$assert(str_contains($navbarView, 'data-catalog-navbar-slot') && str_contains($navbarView, 'data-catalog-nav-toggle') && str_contains($navbarView, 'mnav-brand-orbit'), 'Navbar exposes one catalog slot, a navigation toggle, and the brand orbit');
$assert(str_contains($catalogDock, 'navbarSlot.appendChild(form)') && str_contains($catalogDock, 'slot.appendChild(form)') && str_contains($catalogDock, 'setNavigationPreview'), 'The same catalog form moves between its original slot and the navbar');
$assert(str_contains($navbarJs, 'catalog:navigation-request') && str_contains($navbarJs, 'catalog:navbardock') && str_contains($navbarCss, '@keyframes mnavBrandOrbit'), 'Native navbar panels and the animated catalog takeover cooperate');
$assert(str_contains($catalogLiveSearch, "form.classList.contains('is-docked')") && str_contains($catalogLiveSearch, "catalog:dockstate") && str_contains($catalogLiveSearch, "'is-compact'"), 'Docked live search keeps its results position and compact status');
$assert(str_contains($catalogCss, '.catalog-searchform.is-docked.is-entering') && str_contains($catalogCss, '@keyframes catalogDockArrive') && str_contains($catalogCss, '@media (prefers-reduced-motion: reduce)'), 'Sticky bar has a smooth entrance and reduced-motion fallback');
foreach (['en', 'tr', 'cs'] as $catalogLocale) {
    $catalogTranslations = (string) file_get_contents($root . "/resources/lang/{$catalogLocale}/products.php");
    $navbarTranslations = (string) file_get_contents($root . "/resources/lang/{$catalogLocale}/nav.php");
    $assert(str_contains($catalogTranslations, "'catalog_dock_search'") && str_contains($catalogTranslations, "'catalog_category_prompt'") && str_contains($catalogTranslations, "'catalog_category_back_to'") && str_contains($navbarTranslations, "'catalog_show_navigation'") && str_contains($navbarTranslations, "'catalog_show_filters'"), "Catalog dock, category picker, and navbar toggle are localized for {$catalogLocale}");
}

if ($failures !== []) {
    fwrite(STDERR, "\n" . count($failures) . " contract test(s) failed.\n");
    exit(1);
}

fwrite(STDOUT, "\nAll repository contract tests passed.\n");
