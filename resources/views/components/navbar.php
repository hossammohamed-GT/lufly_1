<?php
/** @var Core\View\View $view */
/** @var Core\Localization\Translator $translator */

$translator = $translator ?? app('Core\Localization\Translator');
$currentLocale = $translator->getLocale();
$supported = $translator->supported();
$route = request()->route();
$params = request()->params();

// Helper to render flag SVG
function renderNavFlag(string $code): string {
    return match ($code) {
        'tr' => '<svg width="20" height="14" viewBox="0 0 1200 800" style="border-radius: 2px; display: inline-block; vertical-align: middle; box-shadow: 0 1px 3px rgba(0,0,0,0.2);"><rect width="1200" height="800" fill="#E30A17"/><circle cx="425" cy="400" r="200" fill="#ffffff"/><circle cx="475" cy="400" r="160" fill="#E30A17"/><polygon points="583.33,400 700.82,438.19 628.2,338.2 628.2,461.8 700.82,361.81" fill="#ffffff"/></svg>',
        'cs' => '<svg width="20" height="14" viewBox="0 0 900 600" style="border-radius: 2px; display: inline-block; vertical-align: middle; box-shadow: 0 1px 3px rgba(0,0,0,0.2);"><rect width="900" height="300" fill="#ffffff"/><rect y="300" width="900" height="300" fill="#D7141A"/><polygon points="0,0 450,300 0,600" fill="#11457E"/></svg>',
        default => '<svg width="20" height="14" viewBox="0 0 60 30" style="border-radius: 2px; display: inline-block; vertical-align: middle; box-shadow: 0 1px 3px rgba(0,0,0,0.2);"><clipPath id="uk-clip-nav"><path d="M0,0 v30 h60 v-30 z"/></clipPath><clipPath id="uk-diag-nav"><path d="M30,15 h30 v15 z v15 h-30 z h-30 v-15 z v-15 h30 z"/></clipPath><g clip-path="url(#uk-clip-nav)"><path d="M0,0 v30 h60 v-30 z" fill="#012169"/><path d="M0,0 L60,30 M60,0 L0,30" stroke="#ffffff" stroke-width="6"/><path d="M0,0 L60,30 M60,0 L0,30" clip-path="url(#uk-diag-nav)" stroke="#C8102E" stroke-width="4"/><path d="M30,0 v30 M0,15 h60" stroke="#ffffff" stroke-width="10"/><path d="M30,0 v30 M0,15 h60" stroke="#C8102E" stroke-width="6"/></g></svg>',
    };
}
?>
<!-- Deante-Style Minimalist Luxury Header with Aquatic Glassmorphism -->
<header class="deante-header lufly-header-monolith">
    <!-- Top Row: Brand Logo, Wide Search Pill, WhatsApp Desk, Theme Toggle, Account, Wishlist, Language Switcher -->
    <div class="deante-header-top">
        <div class="container deante-header-inner">
            <!-- Brand Logo -->
            <a class="deante-logo-wrap" href="<?= e(route('home')) ?>" title="LUFLY Sanitary Architecture">
                <img src="<?= e(asset('frontend/design-system/logo.png')) ?>" alt="LUFLY" class="deante-logo-img">
                <span class="deante-logo-text">lufly</span>
            </a>

            <!-- Wide Search Pill with Magnifying Glass on the Right -->
            <div class="deante-search-container">
                <input type="search" 
                       class="deante-search-pill lufly-search-input" 
                       placeholder="<?= e(trans('nav.search_placeholder', [], $currentLocale)) ?>" 
                       aria-label="Search LUFLY sanitary fixtures"
                       data-locale="<?= e($currentLocale) ?>"
                       autocomplete="off">
                <span class="deante-search-btn" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </span>
                <div class="lufly-search-results"></div>
            </div>

            <!-- Right Utility Icons & Actions -->
            <div class="deante-actions-group">
                <!-- B2B WhatsApp Export Desk (Compact Pill) -->
                <a href="https://wa.me/908503040817?text=<?= rawurlencode('Hello LUFLY, I am inquiring about architectural fixtures and export catalog.') ?>" 
                   target="_blank" 
                   rel="noopener" 
                   class="deante-export-badge" 
                   title="<?= e(trans('nav.export_desk')) ?>">
                    <span class="export-pulse-dot"></span>
                    <span class="export-badge-text"><?= e(trans('nav.export_desk')) ?></span>
                </a>

                <!-- Theme Switcher Button -->
                <button type="button" 
                        class="deante-icon-link deante-theme-toggle" 
                        id="lufly-theme-toggle-btn" 
                        title="<?= e(trans('nav.theme_toggle')) ?>"
                        aria-label="Toggle dark/light theme">
                    <svg class="theme-icon-moon" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                    </svg>
                    <svg class="theme-icon-sun" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="display: none;">
                        <circle cx="12" cy="12" r="5"></circle>
                        <line x1="12" y1="1" x2="12" y2="3"></line>
                        <line x1="12" y1="21" x2="12" y2="23"></line>
                        <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                        <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                        <line x1="1" y1="12" x2="3" y2="12"></line>
                        <line x1="21" y1="12" x2="23" y2="12"></line>
                        <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                        <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
                    </svg>
                </button>

                <!-- User Account / Admin -->
                <a href="<?= auth()->check() ? e(route('admin.dashboard')) : e(route('login')) ?>" 
                   class="deante-icon-link" 
                   title="<?= e(trans('nav.account')) ?>"
                   aria-label="<?= e(trans('nav.account')) ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </a>

                <!-- Wishlist Heart -->
                <a href="<?= e(route('products.index')) ?>" 
                   class="deante-icon-link deante-wishlist-link" 
                   title="<?= e(trans('nav.wishlist')) ?>"
                   aria-label="<?= e(trans('nav.wishlist')) ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                    </svg>
                </a>

                <!-- Multilingual Aquatic Glassmorphic Selector (EN, TR, CS) -->
                <div class="deante-lang-dropdown" id="lufly-lang-menu">
                    <button type="button" 
                            class="deante-lang-current" 
                            id="lufly-lang-toggle" 
                            aria-expanded="false" 
                            aria-haspopup="true"
                            title="<?= e(trans('nav.language')) ?>">
                        <span class="deante-flag-icon"><?= renderNavFlag($currentLocale) ?></span>
                        <span class="deante-lang-code"><?= e(strtoupper($currentLocale)) ?></span>
                        <svg class="deante-chevron-icon" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </button>

                    <div class="deante-lang-menu-box" id="lufly-lang-dropdown-list">
                        <div class="deante-lang-menu-title"><?= e(trans('nav.language')) ?></div>
                        <?php foreach ($supported as $code => $name): ?>
                            <?php
                            if ($code === $currentLocale) {
                                $target = request()->url();
                            } elseif ($route !== null && $route->localized) {
                                $translatedRoute = $translator->trans('routes.' . $route->localizedKey, [], $code);
                                $path = '/' . $code . '/' . ltrim($translatedRoute, '/');
                                foreach ($params as $key => $value) {
                                    if ($key !== 'locale') {
                                        $path = str_replace('{' . $key . '}', rawurlencode((string) $value), $path);
                                    }
                                }
                                $target = url($path);
                            } else {
                                $target = route('lang.switch', ['code' => $code]);
                            }
                            ?>
                            <a class="deante-lang-option<?= $code === $currentLocale ? ' is-current' : '' ?>" 
                               href="<?= e($target) ?>" 
                               lang="<?= e($code) ?>">
                                <span class="deante-flag-mini"><?= renderNavFlag($code) ?></span>
                                <span class="deante-lang-name"><?= e($name) ?></span>
                                <span class="deante-lang-badge"><?= e(strtoupper($code)) ?></span>
                                <?php if ($code === $currentLocale): ?>
                                    <span class="deante-lang-check">&check;</span>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Row: Category Nav Links, Hydrodynamics, Finishes & BIM / Outlet -->
    <div class="deante-subnav-strip">
        <div class="container deante-subnav-inner">
            <nav class="deante-nav-list" aria-label="Main Navigation">
                <a href="<?= e(route('products.index', ['category_id' => 1])) ?>" class="deante-nav-item"><?= e(trans('nav.bathroom')) ?></a>
                <a href="<?= e(route('products.index', ['category_id' => 4])) ?>" class="deante-nav-item"><?= e(trans('nav.kitchen')) ?></a>
                <a href="<?= e(route('products.index', ['sort' => 'newest'])) ?>" class="deante-nav-item"><?= e(trans('nav.latest')) ?></a>
                <a href="<?= e(route('products.index')) ?>" class="deante-nav-item"><?= e(trans('nav.collections')) ?></a>
                <a href="#finishes" class="deante-nav-item"><?= e(trans('nav.finishes')) ?></a>
                <a href="#rituals" class="deante-nav-item"><?= e(trans('nav.rituals')) ?></a>
                <a href="#inspiration" class="deante-nav-item"><?= e(trans('nav.inspirations')) ?></a>
                <a href="#corporate" class="deante-nav-item"><?= e(trans('nav.news')) ?></a>
                <a href="https://wa.me/908503040817?text=<?= rawurlencode('Hello LUFLY, I would like to contact your architectural team.') ?>" target="_blank" rel="noopener" class="deante-nav-item"><?= e(trans('nav.contact')) ?></a>
            </nav>
            <div class="deante-nav-right">
                <a href="https://wa.me/908503040817?text=<?= rawurlencode('Hello LUFLY, please send the 2026 Master Technical PDF Catalog and BIM files.') ?>" 
                   target="_blank" 
                   rel="noopener" 
                   class="deante-nav-item deante-catalog-link">
                    <span>📄 <?= e(trans('nav.download_catalog')) ?></span>
                </a>
                <a href="<?= e(route('products.index')) ?>" class="deante-nav-item deante-outlet-link">
                    <?= e(trans('nav.outlet')) ?>
                </a>
            </div>
        </div>
    </div>
</header>
