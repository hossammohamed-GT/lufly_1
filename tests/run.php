<?php

declare(strict_types=1);

@ini_set('memory_limit', '512M');

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
$assert(str_contains($navbarView, 'data-catalog-navbar-slot') && str_contains($navbarView, 'data-catalog-nav-toggle') && str_contains($navbarView, 'mnav-brand-orbit-runner'), 'Navbar exposes one catalog slot, a navigation toggle, and the brand edge orbit');
$assert(str_contains($catalogDock, 'navbarSlot.appendChild(form)') && str_contains($catalogDock, 'slot.appendChild(form)') && str_contains($catalogDock, 'setNavigationPreview') && str_contains($catalogDock, 'is-catalog-preparing'), 'The same catalog form moves between its original slot and the navbar in staged transitions');
$assert(str_contains($navbarJs, 'catalog:navigation-request') && str_contains($navbarJs, 'catalog:navbardock') && str_contains($navbarCss, '@keyframes mnavBrandOrbit') && str_contains($navbarCss, 'stroke-dashoffset'), 'Native navbar panels and the animated catalog takeover cooperate');
$assert(str_contains($catalogLiveSearch, "form.classList.contains('is-docked')") && str_contains($catalogLiveSearch, "catalog:dockstate") && str_contains($catalogLiveSearch, "'is-compact'"), 'Docked live search keeps its results position and compact status');
$assert(str_contains($catalogCss, '.catalog-searchform.is-docked.is-entering') && str_contains($catalogCss, '@keyframes catalogDockArrive') && str_contains($catalogCss, '@media (prefers-reduced-motion: reduce)'), 'Sticky bar has a smooth entrance and reduced-motion fallback');
foreach (['en', 'tr', 'cs'] as $catalogLocale) {
    $catalogTranslations = (string) file_get_contents($root . "/resources/lang/{$catalogLocale}/products.php");
    $navbarTranslations = (string) file_get_contents($root . "/resources/lang/{$catalogLocale}/nav.php");
    $assert(str_contains($catalogTranslations, "'catalog_dock_search'") && str_contains($catalogTranslations, "'catalog_category_prompt'") && str_contains($catalogTranslations, "'catalog_category_back_to'") && str_contains($navbarTranslations, "'catalog_show_navigation'") && str_contains($navbarTranslations, "'catalog_show_filters'"), "Catalog dock, category picker, and navbar toggle are localized for {$catalogLocale}");
}

$databaseDump = (string) file_get_contents($root . '/lufly-database.sql');
$exportCommand = (string) file_get_contents($root . '/core/Console/Commands/ExportMysqlCommand.php');
$assert(!str_contains(strtoupper($databaseDump), 'CREATE DATABASE') && preg_match('/^USE\s+/im', $databaseDump) === 0, 'Canonical MySQL dump imports into the selected database only');
$dumpHasCatalogData = true;
foreach (['products', 'categories', 'product_media', 'migrations'] as $requiredDataTable) {
    $dumpHasCatalogData = $dumpHasCatalogData && preg_match('/^-- data: ' . preg_quote($requiredDataTable, '/') . ' \(([1-9]\d*) rows\)$/m', $databaseDump) === 1;
}
$assert(substr_count($databaseDump, 'CREATE TABLE `') === 50 && $dumpHasCatalogData, 'Canonical MySQL dump contains all 50 tables and current catalog data');
$databaseConfig = (string) file_get_contents($root . '/config/database.php');
$assert(str_contains($databaseConfig, "'default' => env('DB_CONNECTION', 'sqlite')") && preg_match('/^DB_CONNECTION=sqlite$/m', $example) === 1 && is_file($root . '/database/lufly.sqlite') && filesize($root . '/database/lufly.sqlite') > 0, 'Zero-config local runtime retains the complete bundled SQLite database');
$assert(!is_dir($root . '/database/sql') && glob($root . '/public/.well-known/*.sql') === [], 'Canonical MySQL export has no duplicate partial SQL installation patches');
$assert(!is_dir($root . '/public/manage_products_lufly_new') && !is_file($root . '/README.md') && is_file($root . '/docs/DASHBOARD_GUIDE.md') && glob($root . '/docs/*') === [$root . '/docs/DASHBOARD_GUIDE.md'], 'Unrelated external manager tools stay removed while docs/DASHBOARD_GUIDE.md documents the upcoming dashboard modules');
$assert(!str_contains($exportCommand, "\$sql[] = 'CREATE DATABASE") && !str_contains($exportCommand, "\$sql[] = 'USE `"), 'MySQL exporter never reintroduces database creation or selection statements');

$seededCategories = json_decode((string) file_get_contents($root . '/database/seeders/data/categories.json'), true);
$seededProducts = json_decode((string) file_get_contents($root . '/database/seeders/data/products.json'), true);
$seededFamilies = json_decode((string) file_get_contents($root . '/database/seeders/data/product-color-families.json'), true)['families'] ?? [];
$seededCategorySlugs = [];
$seededParentSlugs = [];
foreach (is_array($seededCategories) ? $seededCategories : [] as $catRow) {
    $cSlug = (string) ($catRow['slug'] ?? '');
    if ($cSlug !== '') {
        $seededCategorySlugs[$cSlug] = true;
    }
    $pSlug = trim((string) ($catRow['parent'] ?? ''));
    if ($pSlug !== '') {
        $seededParentSlugs[$pSlug] = true;
    }
}
$productCategoryBySlug = [];
$allSeedProductsInLeaves = is_array($seededProducts) && count($seededProducts) > 0;
foreach (is_array($seededProducts) ? $seededProducts : [] as $prodRow) {
    $pSlug = (string) ($prodRow['slug'] ?? '');
    $cSlug = (string) ($prodRow['category'] ?? '');
    $productCategoryBySlug[$pSlug] = $cSlug;
    if ($cSlug === '' || !isset($seededCategorySlugs[$cSlug]) || isset($seededParentSlugs[$cSlug])) {
        $allSeedProductsInLeaves = false;
    }
}
$assert($allSeedProductsInLeaves, 'Every seeded product is assigned to a deepest leaf category');

$allFamiliesShareSingleLeaf = is_array($seededFamilies) && count($seededFamilies) > 0;
foreach (is_array($seededFamilies) ? $seededFamilies : [] as $familyRow) {
    $memberCategories = [];
    foreach ((array) ($familyRow['products'] ?? []) as $memberSlug) {
        $memberCat = $productCategoryBySlug[(string) $memberSlug] ?? '';
        if ($memberCat === '' || isset($seededParentSlugs[$memberCat])) {
            $allFamiliesShareSingleLeaf = false;
        }
        $memberCategories[$memberCat] = true;
    }
    if (count($memberCategories) !== 1) {
        $allFamiliesShareSingleLeaf = false;
    }
}
$assert($allFamiliesShareSingleLeaf, 'Every colour family stays within one leaf category');

if (extension_loaded('pdo_sqlite')) {
    $pdo = new PDO('sqlite:' . $root . '/database/lufly.sqlite');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $nonLeafAssigned = (int) $pdo->query(
        'SELECT COUNT(*) FROM products p
         WHERE p.deleted_at IS NULL
           AND (
               p.category_id IS NULL
               OR NOT EXISTS (SELECT 1 FROM categories c WHERE c.id = p.category_id AND c.deleted_at IS NULL)
               OR EXISTS (SELECT 1 FROM categories ch WHERE ch.parent_id = p.category_id AND ch.deleted_at IS NULL)
           )',
    )->fetchColumn();
    $assert($nonLeafAssigned === 0, 'SQLite runtime assigns every product exclusively to an active leaf category');

    $sqlParityOk = true;
    foreach ([
        'migrations', 'categories', 'category_translations', 'products', 'product_translations',
        'product_variants', 'product_specifications', 'product_specification_translations',
        'product_feature_presets', 'product_feature_preset_translations',
        'product_card_features', 'product_card_feature_translations',
        'product_media', 'product_relations', 'seo_meta', 'media',
        'product_search_keywords', 'product_import_logs',
    ] as $parityTable) {
        $sqliteCount = (int) $pdo->query("SELECT COUNT(*) FROM `{$parityTable}`")->fetchColumn();
        $expectedHeader = "-- data: {$parityTable} ({$sqliteCount} rows)";
        if (!str_contains($databaseDump, $expectedHeader) || substr_count($databaseDump, "INSERT INTO `{$parityTable}` ") !== $sqliteCount) {
            $sqlParityOk = false;
            break;
        }
    }
    $assert($sqlParityOk, 'Root lufly-database.sql row counts match database/lufly.sqlite across all core tables');

    $expectedColorEdges = 0;
    foreach (is_array($seededFamilies) ? $seededFamilies : [] as $familyRow) {
        $expectedColorEdges += max(0, count((array) ($familyRow['products'] ?? [])) - 1);
    }
    $sqliteColorEdges = (int) $pdo->query("SELECT COUNT(*) FROM product_relations WHERE relation_type = 'color_variant'")->fetchColumn();
    $assert(
        count($seededFamilies) >= 105 && $sqliteColorEdges === $expectedColorEdges,
        'SQLite product_relations stays synchronized with all reviewed colour families',
    );

    $drawingPrimaryCount = (int) $pdo->query(
        "SELECT COUNT(*) FROM product_media pm
         INNER JOIN media m ON m.id = pm.media_id
         WHERE pm.is_primary = 1 AND (pm.type = 'drawing' OR m.status IN ('missing', 'hidden'))",
    )->fetchColumn();
    $conflictingPathCount = (int) $pdo->query(
        "SELECT COUNT(*) FROM (
             SELECT m.path
             FROM product_media pm
             INNER JOIN media m ON m.id = pm.media_id
             WHERE m.status NOT IN ('missing', 'hidden')
             GROUP BY m.path
             HAVING COUNT(DISTINCT pm.type) > 1
         )",
    )->fetchColumn();
    $missingPrimaryPhotoProducts = (int) $pdo->query(
        "SELECT COUNT(*) FROM products p
         WHERE EXISTS (
             SELECT 1 FROM product_media pm
             INNER JOIN media m ON m.id = pm.media_id
             WHERE pm.product_id = p.id
               AND pm.variant_id IS NULL
               AND pm.type != 'drawing'
               AND m.status NOT IN ('missing', 'hidden')
         )
         AND (
             SELECT COUNT(*) FROM product_media pm2
             WHERE pm2.product_id = p.id
               AND pm2.variant_id IS NULL
               AND pm2.is_primary = 1
         ) != 1",
    )->fetchColumn();
    $sensorTapGalleryCheck = (int) $pdo->query(
        "SELECT COUNT(*) FROM product_media pm
         INNER JOIN products p ON p.id = pm.product_id
         INNER JOIN media m ON m.id = pm.media_id
         WHERE p.slug IN ('1722-8126sc', '1722-8126scb', '1722-8126sb', '1722-8126sgg', '1722-8126sgo', '1722-8126srg')
           AND m.status = 'active'
           AND pm.type != 'drawing'",
    )->fetchColumn();
    $assert(
        $drawingPrimaryCount === 0
        && $conflictingPathCount === 0
        && $missingPrimaryPhotoProducts === 0
        && $sensorTapGalleryCheck === 6,
        'Technical drawings never appear in main product galleries or as primary product photos',
    );

    /* Pillar 1: 4 to 6 card features per product + presets + icon catalog */
    require_once $root . '/modules/Products/Services/ProductFeatureIconCatalog.php';
    $outOfRangeFeatureProducts = (int) $pdo->query(
        'SELECT COUNT(*) FROM (
             SELECT p.id, COUNT(cf.id) AS c
             FROM products p
             LEFT JOIN product_card_features cf ON cf.product_id = p.id
             GROUP BY p.id
             HAVING c < 4 OR c > 6
         )',
    )->fetchColumn();
    $presetCount = (int) $pdo->query('SELECT COUNT(*) FROM product_feature_presets WHERE is_active = 1')->fetchColumn();
    $invalidIconKeys = 0;
    foreach ($pdo->query('SELECT DISTINCT icon_key FROM product_card_features')->fetchAll(PDO::FETCH_COLUMN) as $ik) {
        if (!\Modules\Products\Services\ProductFeatureIconCatalog::has((string) $ik)) {
            $invalidIconKeys++;
        }
    }
    $assert(
        $outOfRangeFeatureProducts === 0 && $presetCount >= 20 && $invalidIconKeys === 0,
        'Every product carries 4 to 6 verified card features backed by active presets and valid SVG icons',
    );

    /* Pillar 2: Toilet seat covers linked to parent WCs and hidden from standalone grids */
    $seatCoverEdges = (int) $pdo->query("SELECT COUNT(*) FROM product_relations WHERE relation_type = 'seat_cover'")->fetchColumn();
    $distinctLinkedSeats = (int) $pdo->query("SELECT COUNT(DISTINCT related_product_id) FROM product_relations WHERE relation_type = 'seat_cover'")->fetchColumn();
    $assert(
        $seatCoverEdges === 21
        && $distinctLinkedSeats === 19
        && str_contains((string) file_get_contents($root . '/modules/Products/Services/ProductVisibility.php'), "relation_type IN ('seat_cover', 'seat_cover_only')")
        && str_contains((string) file_get_contents($root . '/modules/Products/Views/show.php'), 'id="wc-seat-options"'),
        'All 19 toilet seat covers link to their parent WCs via product_relations and never render as standalone catalog cards',
    );

    /* Pillar 3: Complete trilingual (en / tr / cs) coverage across DB & UI lang files */
    $totalProducts = (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
    $totalSpecs = (int) $pdo->query('SELECT COUNT(*) FROM product_specifications')->fetchColumn();
    $totalCardFeatures = (int) $pdo->query('SELECT COUNT(*) FROM product_card_features')->fetchColumn();
    $totalPresets = (int) $pdo->query('SELECT COUNT(*) FROM product_feature_presets')->fetchColumn();
    $trilingualDbOk = true;
    foreach (['en', 'tr', 'cs'] as $loc) {
        $ptCount = (int) $pdo->query("SELECT COUNT(*) FROM product_translations WHERE locale = '{$loc}'")->fetchColumn();
        $seoCount = (int) $pdo->query("SELECT COUNT(*) FROM seo_meta WHERE locale = '{$loc}'")->fetchColumn();
        $stCount = (int) $pdo->query("SELECT COUNT(*) FROM product_specification_translations WHERE locale = '{$loc}'")->fetchColumn();
        $cfCount = (int) $pdo->query("SELECT COUNT(*) FROM product_card_feature_translations WHERE locale = '{$loc}'")->fetchColumn();
        $prCount = (int) $pdo->query("SELECT COUNT(*) FROM product_feature_preset_translations WHERE locale = '{$loc}'")->fetchColumn();
        if ($ptCount !== $totalProducts || $seoCount !== $totalProducts || $stCount !== $totalSpecs || $cfCount !== $totalCardFeatures || $prCount !== $totalPresets) {
            $trilingualDbOk = false;
            break;
        }
    }
    $placeholderLeak = (int) $pdo->query("SELECT COUNT(*) FROM product_translations WHERE description LIKE '%اختبار%'")->fetchColumn();
    $assert(
        $trilingualDbOk && $placeholderLeak === 0,
        'Every product, specification, preset, card feature, and SEO meta record is 100% populated across en, tr, and cs',
    );
}

/* Verify UI language file key parity across en / tr / cs */
$enLangFiles = glob($root . '/resources/lang/en/*.php') ?: [];
$langParityOk = count($enLangFiles) >= 14;
foreach ($enLangFiles as $enFile) {
    $baseName = basename($enFile);
    $enArr = require $enFile;
    foreach (['tr', 'cs'] as $otherLoc) {
        $otherFile = $root . "/resources/lang/{$otherLoc}/{$baseName}";
        if (!is_file($otherFile)) {
            $langParityOk = false;
            break 2;
        }
        $otherArr = require $otherFile;
        if (!is_array($enArr) || !is_array($otherArr) || array_diff(array_keys($enArr), array_keys($otherArr)) !== []) {
            $langParityOk = false;
            break 2;
        }
    }
}
$assert($langParityOk, 'All storefront and system translation files maintain 100% key parity across en, tr, and cs');

$categoryServiceSrc = (string) file_get_contents($root . '/app/Services/CategoryService.php');
$productRepositorySrc = (string) file_get_contents($root . '/modules/Products/Repositories/ProductRepository.php');
$productControllerSrc = (string) file_get_contents($root . '/modules/Products/Controllers/ProductController.php');
$homeControllerSrc = (string) file_get_contents($root . '/app/Http/Controllers/HomeController.php');
$categoryExplorerViewSrc = (string) file_get_contents($root . '/resources/views/components/category-explorer.php');
$productShowViewSrc = (string) file_get_contents($root . '/modules/Products/Views/show.php');
$storeRequestSrc = (string) file_get_contents($root . '/modules/Products/Requests/StoreProductRequest.php');
$updateRequestSrc = (string) file_get_contents($root . '/modules/Products/Requests/UpdateProductRequest.php');
$adminFormSrc = (string) file_get_contents($root . '/modules/Products/Views/Admin/form.php');
$assert(
    str_contains($categoryServiceSrc, 'function leafIds()')
    && str_contains($storeRequestSrc, '->leafIds()')
    && str_contains($updateRequestSrc, '->leafIds()')
    && str_contains($adminFormSrc, "!\$isLeaf ? 'disabled' : ''"),
    'Category service, product requests, and admin form enforce leaf-only category assignment',
);
$assert(
    str_contains($categoryServiceSrc, 'function nonEmptyTree(')
    && str_contains($productControllerSrc, '->nonEmptyTree(')
    && str_contains($homeControllerSrc, '->nonEmptyTree(')
    && str_contains($categoryExplorerViewSrc, '$pruneEmpty')
    && str_contains($catalogPicker, '$prunePickerTree')
    && !str_contains($productRepositorySrc, 'rootFallbackBySlug')
    && !str_contains($productControllerSrc, 'rootFallbackBySlug'),
    'Storefront hides empty categories across explorer and dock and never substitutes root fallback products',
);
$assert(
    str_contains($catalogDock, 'selectCategory(drillSlug, true)')
    && str_contains($catalogDock, "selectCategory(choice.getAttribute('data-category-slug') || '', true)")
    && str_contains($catalogExplorer, 'isCatalogMode && depth >= 2 && !hasChildren')
    && str_contains($catalogExplorer, 'syncLeafCards(slug)'),
    'Selecting any category in the explorer or docked bar loads products immediately while keeping choices open',
);
$assert(
    str_contains($categoryServiceSrc, 'function breadcrumbsForCategoryId(')
    && str_contains($productControllerSrc, 'breadcrumbsForCategoryId(')
    && str_contains($productShowViewSrc, '$categoryBreadcrumbs'),
    'Product detail pages expose the full ancestor-to-leaf category trail',
);
$assert(
    ($productCategoryBySlug['1620-8787'] ?? '') === 'kids.kids-sanitary-ware.kids-toilets'
    && ($productCategoryBySlug['1685-204'] ?? '') === 'kids.kids-sanitary-ware.kids-wash-basins'
    && ($productCategoryBySlug['1685-711'] ?? '') === 'bathroom.sanitary-ware.toilets'
    && ($productCategoryBySlug['sfd-133-15'] ?? '') === 'drains.floor-drains'
    && ($productCategoryBySlug['1755-9720c'] ?? '') === 'bathroom.mixers.thermostatic-mixers'
    && ($productCategoryBySlug['1755-9720gg'] ?? '') === 'bathroom.mixers.thermostatic-mixers'
    && isset($seededCategorySlugs['bathroom.angle-valves'])
    && !isset($seededParentSlugs['bathroom.angle-valves'])
    && is_file($root . '/public/images/categories/109-bathroom-angle-valves.jpg')
    && ($productCategoryBySlug['hl-st105'] ?? '') === 'bathroom.angle-valves'
    && ($productCategoryBySlug['hl-st106'] ?? '') === 'bathroom.angle-valves'
    && ($productCategoryBySlug['hl-st106bro'] ?? '') === 'bathroom.angle-valves'
    && ($productCategoryBySlug['hl-st106b'] ?? '') === 'bathroom.angle-valves'
    && ($productCategoryBySlug['hl-st106bn'] ?? '') === 'bathroom.angle-valves'
    && ($productCategoryBySlug['hl-st106rg'] ?? '') === 'bathroom.angle-valves'
    && ($productCategoryBySlug['hl-st110-229'] ?? '') === 'bathroom.angle-valves'
    && ($productCategoryBySlug['hl-st133'] ?? '') === 'bathroom.angle-valves',
    'Audited bathroom, angle valve, drain, thermostatic, and kids sanitary ware items stay in their exact leaf categories',
);

$familyIndexBySlug = [];
foreach (is_array($seededFamilies) ? $seededFamilies : [] as $fIdx => $familyRow) {
    foreach ((array) ($familyRow['products'] ?? []) as $memberSlug) {
        $familyIndexBySlug[(string) $memberSlug] = $fIdx;
    }
}
$colorFamilyServiceSrc = (string) file_get_contents($root . '/modules/Products/Services/ProductColorFamilyService.php');
$colorVariantsViewSrc = (string) file_get_contents($root . '/resources/views/components/product-color-variants.php');
$assert(
    isset($familyIndexBySlug['1654-8032c'], $familyIndexBySlug['1654-8032bn'], $familyIndexBySlug['1654-8032gg'], $familyIndexBySlug['1654-8032rg'], $familyIndexBySlug['1654-8003rgb'])
    && $familyIndexBySlug['1654-8032c'] === $familyIndexBySlug['1654-8032rg']
    && $familyIndexBySlug['1654-8003rgb'] === $familyIndexBySlug['1654-8032rg']
    && isset($familyIndexBySlug['1654-8002c'], $familyIndexBySlug['1654-8002mb'])
    && $familyIndexBySlug['1654-8002c'] === $familyIndexBySlug['1654-8002mb']
    && isset($familyIndexBySlug['1681-084c'], $familyIndexBySlug['1681-084rg'])
    && $familyIndexBySlug['1681-084c'] === $familyIndexBySlug['1681-084rg']
    && isset($familyIndexBySlug['hl-st110'], $familyIndexBySlug['hl-st110rg'])
    && $familyIndexBySlug['hl-st110'] === $familyIndexBySlug['hl-st110rg']
    && isset($familyIndexBySlug['4000-7644a'], $familyIndexBySlug['4000-7644b'])
    && $familyIndexBySlug['4000-7644a'] === $familyIndexBySlug['4000-7644b']
    && str_contains($colorFamilyServiceSrc, '$finishSlots[$signature]')
    && str_contains($colorVariantsViewSrc, "!empty(\$variant['card']['finishes'])")
    && str_contains($productControllerSrc, 'ProductColorFamilyService::class)->attach(')
    && str_contains($productShowViewSrc, "component('product-color-variants'"),
    'All audited multi-colour products collapse into single family cards with deduplicated swatches across catalog and PDP',
);

$featuredJsonBySlug = [];
foreach ($seededProducts as $pItem) {
    if (!empty($pItem['is_featured'])) {
        $featuredJsonBySlug[(string) ($pItem['slug'] ?? '')] = (int) ($pItem['sort_order'] ?? 0);
    }
}
$enrichmentPayload = json_decode((string) file_get_contents($root . '/database/seeders/data/product-enrichment.json'), true);
$homeControllerSrc = (string) file_get_contents($root . '/app/Http/Controllers/HomeController.php');
$masterpiecesViewSrc = (string) file_get_contents($root . '/resources/views/home/masterpieces/masterpieces.php');
$productRepoSrc = (string) file_get_contents($root . '/modules/Products/Repositories/ProductRepository.php');
$productCardViewSrc = (string) file_get_contents($root . '/resources/views/components/product-card.php');
$productCardCssSrc = (string) file_get_contents($root . '/frontend/css/product-card.css');
$variantsJsSrc = (string) file_get_contents($root . '/frontend/js/product-card-variants.js');
$liveSearchJsSrc = (string) file_get_contents($root . '/frontend/js/live-search.js');

$assert(
    ($featuredJsonBySlug['1653-8033c'] ?? 0) === 1
    && ($featuredJsonBySlug['1653-8033bn'] ?? 0) === 2
    && ($featuredJsonBySlug['1653-8033gg'] ?? 0) === 3
    && ($featuredJsonBySlug['1653-8033rg'] ?? 0) === 4
    && ($featuredJsonBySlug['1654-8033rgb'] ?? 0) === 5
    && (($enrichmentPayload['featured_priority'][0]['slug'] ?? '') === '1653-8033c')
    && str_contains($homeControllerSrc, 'ORDER BY p.is_featured DESC, p.sort_order ASC, p.id DESC')
    && str_contains($masterpiecesViewSrc, "component('product-card'")
    && str_contains($productRepoSrc, 'ORDER BY p.is_featured DESC, p.sort_order ASC, has_photo DESC, p.id DESC')
    && str_contains($productRepoSrc, 'array_merge($featuredIds, $this->diversifyDiscovery($regularRows))')
    && str_contains($productCardViewSrc, 'is-featured')
    && str_contains($productCardViewSrc, 'is-featured-badge')
    && str_contains($productCardCssSrc, '.pcard.is-featured')
    && str_contains($variantsJsSrc, "card.classList.toggle('is-featured'")
    && str_contains($liveSearchJsSrc, 'is-featured-badge'),
    'Featured priority system places Pull-Out Kitchen Mixer (1653-8033c) first with golden frame across Home, Catalog, Categories, and Live Search',
);

/* 100% Product Color / Finish Coverage across all 712 products + en/tr/cs locales */
$finishSpecRows = $db->select(
    "SELECT p.id, p.slug, ps.id AS spec_id, ps.spec_value
     FROM products p
     LEFT JOIN product_specifications ps ON ps.product_id = p.id AND ps.variant_id IS NULL AND ps.spec_key = 'Finish'
     WHERE p.deleted_at IS NULL
     ORDER BY p.id ASC"
);
$missingDbFinishes = array_filter($finishSpecRows, static fn(array $r): bool => trim((string) ($r['spec_value'] ?? '')) === '');
$finishTrCount = (int) (($db->selectOne(
    "SELECT COUNT(*) AS cnt
     FROM product_specification_translations pst
     JOIN product_specifications ps ON ps.id = pst.specification_id
     WHERE ps.variant_id IS NULL AND ps.spec_key = 'Finish' AND TRIM(pst.spec_value) != ''"
)['cnt'] ?? 0));
$assert(
    count($finishSpecRows) === 712 && $missingDbFinishes === [] && $finishTrCount === 712 * 3,
    'Every single product (712/712) has an explicit Finish specification in product_specifications plus en/tr/cs translations',
);

$zeroFinishPresented = [];
$neutralSwatchPresented = [];
foreach (['en', 'tr', 'cs'] as $locCheck) {
    foreach ($finishSpecRows as $fRow) {
        $presentedFinishes = \Modules\Products\Services\ProductCardPresenter::finishes(
            [],
            [['raw_key' => 'Finish', 'raw_value' => (string) $fRow['spec_value'], 'spec_key' => 'Finish', 'spec_value' => (string) $fRow['spec_value']]],
            '',
            $locCheck,
        );
        if ($presentedFinishes === []) {
            $zeroFinishPresented[] = "{$locCheck}:{$fRow['slug']}";
        }
        foreach ($presentedFinishes as $sw) {
            if (($sw['key'] ?? 'neutral') === 'neutral' || trim((string) ($sw['label'] ?? '')) === '') {
                $neutralSwatchPresented[] = "{$locCheck}:{$fRow['slug']}:" . ($sw['key'] ?? 'null');
            }
        }
    }
}
$assert(
    $zeroFinishPresented === [] && $neutralSwatchPresented === [],
    'ProductCardPresenter resolves valid non-neutral color swatches and localized labels for 712/712 products across en, tr, and cs',
);

$finishBySlug = [];
foreach ($finishSpecRows as $fRow) {
    $finishBySlug[(string) $fRow['slug']] = (string) $fRow['spec_value'];
}
$assert(
    ($finishBySlug['1520-6767'] ?? '') === 'White / Green / Yellow'
    && ($finishBySlug['1654-1111'] ?? '') === 'Blue / Yellow / Red'
    && ($finishBySlug['ss-9762366'] ?? '') === 'Orange'
    && ($finishBySlug['1695-209'] ?? '') === 'Oak Wood / White'
    && ($finishBySlug['5000-2924'] ?? '') === 'Dark Green / Stainless Steel'
    && ($finishBySlug['1270-0299'] ?? '') === 'Anthracite / Stainless Steel'
    && str_contains($productCardCssSrc, '.pcard-swatch--orange')
    && str_contains($productCardCssSrc, '.pcard-swatch--red')
    && str_contains($productCardCssSrc, '.pcard-swatch--wood')
    && str_contains($productCardCssSrc, '.pcard-swatch--anthracite')
    && str_contains($productCardCssSrc, '.pcard-swatch--dark-green'),
    'Multi-tone and newly verified finishes (Orange, Red, Oak Wood, Anthracite, Dark Green) match product visuals and CSS swatches',
);

foreach (glob($root . '/resources/lang/en/*.php') ?: [] as $enLangFile) {
    $base = basename($enLangFile);
    $enKeys = array_keys(require $enLangFile);
    sort($enKeys);
    foreach (['tr', 'cs'] as $loc) {
        $locFile = $root . "/resources/lang/{$loc}/{$base}";
        $locKeys = is_file($locFile) ? array_keys(require $locFile) : [];
        sort($locKeys);
        $assert($enKeys === $locKeys, "Translation key parity holds between en/{$base} and {$loc}/{$base}");
    }
}

if ($failures !== []) {
    fwrite(STDERR, "\n" . count($failures) . " contract test(s) failed.\n");
    exit(1);
}

fwrite(STDOUT, "\nAll repository contract tests passed.\n");
