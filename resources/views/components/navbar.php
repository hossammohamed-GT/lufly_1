<?php
$view->pushStyle('frontend/components/navbar/navbar.css');
$view->pushScript('frontend/components/navbar/navbar.js');

$translator = $translator ?? app('Core\Localization\Translator');
$currentLocale = $translator->getLocale();
$supported = $translator->supported();
$route = request()->route();
$params = request()->params();
$currentUrl = (string) request()->url();
$currentPath = rtrim((string) parse_url($currentUrl, PHP_URL_PATH), '/');
$currentQuery = (string) (parse_url($currentUrl, PHP_URL_QUERY) ?? '');

$isActive = static function (string $target) use ($currentPath, $currentQuery): bool {
    $parts = parse_url($target) ?: [];
    $path = rtrim((string) ($parts['path'] ?? ''), '/');

    if ($path === '' || $path !== $currentPath) {
        return false;
    }

    $query = (string) ($parts['query'] ?? '');

    return $query === '' || $query === $currentQuery;
};

$favoritesOn = feature('favorites', true) && class_exists(\Modules\Favorites\Services\FavoriteService::class);
$plannerOn = feature('planner', true)
    && !(bool) config('planner.coming_soon', true)
    && class_exists(\Modules\Planner\Services\PlannerService::class);
$savedCount = 0;
if ($favoritesOn) {
    $savedCount = app(\Modules\Favorites\Services\FavoriteService::class)->count();
}

$boxOn = feature('box', true) && class_exists(\Modules\Box\Services\BoxService::class);
$boxCount = 0;
if ($boxOn) {
    $boxCount = app(\Modules\Box\Services\BoxService::class)->count();
}

$indexLinks = [
    ['key' => 'bathroom', 'url' => route('products.index', ['category' => 'bathroom-ceramics'])],
    ['key' => 'kitchen', 'url' => route('products.index', ['category' => 'sink-mixers'])],
    ['key' => 'latest', 'url' => route('products.index', ['sort' => 'newest'])],
    ['key' => 'collections', 'url' => route('products.index')],
    ['key' => 'finishes', 'url' => route('home') . '#finishes'],
    ['key' => 'rituals', 'url' => route('home') . '#rituals'],
    ['key' => 'inspirations', 'url' => route('home') . '#inspiration'],
    ['key' => 'news', 'url' => route('home') . '#corporate'],
    [
        'key' => 'contact',
        'url' => route('contact'),
    ],
];

if ($favoritesOn) {
    array_splice($indexLinks, count($indexLinks) - 1, 0, [[
        'key' => 'favorites',
        'url' => route('favorites.index'),
    ]]);
}

if ($boxOn) {
    array_splice($indexLinks, count($indexLinks) - 1, 0, [[
        'key' => 'box',
        'url' => route('box.index'),
    ]]);
}

if ($plannerOn) {
    array_splice($indexLinks, count($indexLinks) - 1, 0, [[
        'key' => 'planner',
        'url' => route('planner.index'),
    ]]);
}

$railLinks = array_slice($indexLinks, 0, 4);

$getSectionIcon = static function (string $key): string {
    return match ($key) {
        'bathroom' => '<svg class="icon mnav-cat-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6h6a2 2 0 0 1 2 2v2H7V8a2 2 0 0 1 2-2z"></path><path d="M5 10h14a2 2 0 0 1 2 2v2a6 6 0 0 1-6 6H9a6 6 0 0 1-6-6v-2a2 2 0 0 1 2-2z"></path><line x1="7" y1="20" x2="7" y2="22"></line><line x1="17" y1="20" x2="17" y2="22"></line></svg>',
        'kitchen' => '<svg class="icon mnav-cat-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 14h18"></path><path d="M5 14V6a3 3 0 0 1 6 0v2"></path><path d="M19 14v4a3 3 0 0 1-3 3H8a3 3 0 0 1-3-3v-4"></path><circle cx="8" cy="8" r="1"></circle></svg>',
        'latest' => '<svg class="icon mnav-cat-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>',
        'collections' => '<svg class="icon mnav-cat-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>',
        'finishes' => '<svg class="icon mnav-cat-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 3a9 9 0 0 1 9 9c0 2.5-2 4.5-4.5 4.5s-2.5-2-2.5-2H10"></path></svg>',
        'rituals' => '<svg class="icon mnav-cat-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"></path></svg>',
        'inspirations' => '<svg class="icon mnav-cat-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"></polygon></svg>',
        'news' => '<svg class="icon mnav-cat-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"></path><path d="M18 14h-8"></path><path d="M15 18h-5"></path><path d="M10 6h8v4h-8V6Z"></path></svg>',
        'favorites' => '<svg class="icon mnav-cat-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20.6 4.2 12.8a5.1 5.1 0 0 1 0-7.2 5.1 5.1 0 0 1 7.2 0l.6.6.6-.6a5.1 5.1 0 0 1 7.2 0 5.1 5.1 0 0 1 0 7.2Z"></path></svg>',
        'box' => '<svg class="icon mnav-cat-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 8.5 5 4h14l2 4.5"></path><path d="M3 8.5h18V19a1.5 1.5 0 0 1-1.5 1.5h-15A1.5 1.5 0 0 1 3 19Z"></path><path d="M12 8.5V20.5"></path></svg>',
        'planner' => '<svg class="icon mnav-cat-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"></rect><path d="M3 10h18M10 4v16"></path></svg>',
        'contact' => '<svg class="icon mnav-cat-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>',
        default => '<svg class="icon mnav-cat-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"></circle></svg>'
    };
};

$catalogUrl = route('contact');

$languageLinks = [];

foreach ($supported as $code => $name) {
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

    $languageLinks[$code] = ['label' => $name, 'target' => $target];
}
?>
<header class="mnav" data-navbar>
    <div class="mnav-slab">
        <span class="mnav-grain" aria-hidden="true"></span>
        <span class="mnav-beam" aria-hidden="true"></span>
<div class="container mnav-top">
            <a class="mnav-brand" href="<?= e(route('home')) ?>" title="LUFLY Sanitary Architecture">
                <img class="mnav-brand-img"
src="<?= e(asset('images/logo.png')) ?>"
                    alt="LUFLY"
                     width="66"
                     height="42"
                     decoding="async"
                     fetchpriority="high">
            </a>

            <div class="mnav-search" data-search id="mnav-search">
                <div class="mnav-field">
                    <svg class="icon mnav-field-icon" width="18" height="18" viewBox="0 0 24 24" aria-hidden="true">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input type="search"
                           class="mnav-input"
                           data-search-input
                           data-locale="<?= e($currentLocale) ?>"
                           placeholder="<?= e(trans('nav.search_placeholder', [], $currentLocale)) ?>"
                           aria-label="<?= e(trans('nav.search', [], $currentLocale)) ?>"
                           autocomplete="off">
                    <kbd class="mnav-kbd" aria-hidden="true">/</kbd>
                    <button type="button" class="mnav-search-close" data-search-close aria-label="<?= e(trans('nav.close', [], $currentLocale)) ?>">
                        <svg class="icon icon-sm" viewBox="0 0 24 24" aria-hidden="true">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>
                <div class="mnav-results" data-search-results aria-live="polite" aria-label="<?= e(trans('nav.search', [], $currentLocale)) ?>"></div>
            </div>

            <div class="mnav-actions">
                <button type="button"
                        class="mnav-btn-menu"
                        data-index-toggle
                        aria-controls="mnav-dropdown-bar"
                        aria-expanded="false"
                        aria-label="<?= e(trans('nav.open_index', [], $currentLocale)) ?>">
                    <span class="mnav-btn-menu-bars" aria-hidden="true"><i></i><i></i><i></i></span>
                    <span class="mnav-btn-menu-text"><?= e(trans('nav.index', [], $currentLocale)) ?></span>
                    <svg class="icon icon-sm mnav-btn-menu-chevron" viewBox="0 0 24 24" aria-hidden="true">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </button>

                <button type="button"
                        class="mnav-icon-btn mnav-search-open"
                        data-search-toggle
                        aria-controls="mnav-search"
                        aria-expanded="false"
                        aria-label="<?= e(trans('nav.search', [], $currentLocale)) ?>">
                    <svg class="icon" viewBox="0 0 24 24" aria-hidden="true">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </button>

                <button type="button"
                        class="mnav-icon-btn mnav-theme"
                        data-theme-toggle
                        id="lufly-theme-toggle-btn"
                        title="<?= e(trans('nav.theme_toggle', [], $currentLocale)) ?>"
                        aria-label="<?= e(trans('nav.theme_toggle', [], $currentLocale)) ?>">
                    <span class="mnav-theme-icons" aria-hidden="true">
                        <svg class="icon mnav-moon" viewBox="0 0 24 24">
                            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                        </svg>
                        <svg class="icon mnav-sun" viewBox="0 0 24 24">
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
                    </span>
                </button>

                <?php if ($plannerOn): ?>
                    <a href="<?= e(route('planner.index')) ?>"
                       class="mnav-icon-btn mnav-plan"
                       title="<?= e(trans('nav.planner', [], $currentLocale)) ?>"
                       aria-label="<?= e(trans('nav.planner', [], $currentLocale)) ?>">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="3" y="4" width="18" height="16" rx="2"></rect>
                            <path d="M3 10h18M10 4v16"></path>
                        </svg>
                    </a>
                <?php endif; ?>

                <?php if ($boxOn): ?>
                    <a href="<?= e(route('box.index')) ?>"
                       class="mnav-icon-btn mnav-box<?= $boxCount > 0 ? ' has-items' : '' ?>"
                       title="<?= e(trans("nav.box", [], $currentLocale)) ?>"
                       aria-label="<?= e(trans("nav.box", [], $currentLocale)) ?>">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M4 8h16l-1.2 11.2a2 2 0 0 1-2 1.8H7.2a2 2 0 0 1-2-1.8Z"></path>
                            <path d="M9 8V6a3 3 0 0 1 6 0v2"></path>
                        </svg>
                        <span class="mnav-fav-badge" data-box-count><?= $boxCount > 0 ? (int) $boxCount : '' ?></span>
                    </a>
                <?php endif; ?>

                <?php if ($favoritesOn): ?>
                    <a href="<?= e(route('favorites.index')) ?>"
                       class="mnav-icon-btn mnav-fav<?= $savedCount > 0 ? ' has-items' : '' ?>"
                       title="<?= e(trans('nav.favorites', [], $currentLocale)) ?>"
                       aria-label="<?= e(trans('nav.favorites', [], $currentLocale)) ?>">
                        <svg class="icon" viewBox="0 0 24 24" fill="<?= $savedCount > 0 ? 'currentColor' : 'none' ?>"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M12 20.6 4.2 12.8a5.1 5.1 0 0 1 0-7.2 5.1 5.1 0 0 1 7.2 0l.6.6.6-.6a5.1 5.1 0 0 1 7.2 0 5.1 5.1 0 0 1 0 7.2Z"></path>
                        </svg>
                        <span class="mnav-fav-badge" data-fav-count><?= $savedCount > 0 ? (int) $savedCount : '' ?></span>
                    </a>
                <?php endif; ?>

                <a href="<?= auth()->check() ? e(route('admin.dashboard')) : e(route('login')) ?>"
                   class="mnav-icon-btn mnav-account"
                   title="<?= e(trans('nav.account', [], $currentLocale)) ?>"
                   aria-label="<?= e(trans('nav.account', [], $currentLocale)) ?>">
                    <svg class="icon" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </a>

                <div class="mnav-lang" data-lang-menu>
                    <button type="button"
                            class="mnav-lang-btn"
                            data-lang-toggle
                            aria-expanded="false"
                            aria-haspopup="true"
                            title="<?= e(trans('nav.language', [], $currentLocale)) ?>">
                        <span class="mnav-flag" aria-hidden="true"><?= $view->component('flag', ['code' => $currentLocale]) ?></span>
                        <span class="mnav-lang-code"><?= e(strtoupper($currentLocale)) ?></span>
                        <svg class="icon icon-sm mnav-chevron" viewBox="0 0 24 24" aria-hidden="true">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </button>

                    <div class="mnav-lang-menu" role="menu" aria-label="<?= e(trans('nav.language', [], $currentLocale)) ?>">
                        <div class="mnav-lang-menu-title"><?= e(trans('nav.language', [], $currentLocale)) ?></div>
                        <?php foreach ($languageLinks as $code => $link): ?>
                            <?php if ($code === $currentLocale): ?>
                                <?php ?>
                                <span class="mnav-lang-option is-current"
                                      role="menuitem"
                                      aria-current="true"
                                      lang="<?= e($code) ?>">
                                    <span class="mnav-flag" aria-hidden="true"><?= $view->component('flag', ['code' => $code]) ?></span>
                                    <span class="mnav-lang-name"><?= e($link['label']) ?></span>
                                    <span class="mnav-lang-badge"><?= e(strtoupper($code)) ?></span>
                                    <span class="mnav-lang-check" aria-hidden="true">&check;</span>
                                </span>
                            <?php else: ?>
                                <a class="mnav-lang-option"
                                   role="menuitem"
                                   href="<?= e($link['target']) ?>"
                                   lang="<?= e($code) ?>">
                                    <span class="mnav-flag" aria-hidden="true"><?= $view->component('flag', ['code' => $code]) ?></span>
                                    <span class="mnav-lang-name"><?= e($link['label']) ?></span>
                                    <span class="mnav-lang-badge"><?= e(strtoupper($code)) ?></span>
                                </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>

                <button type="button"
                        class="mnav-icon-btn mnav-bloom"
                        data-nav-open
                        aria-controls="mnav-sheet"
                        aria-expanded="false"
                        aria-label="<?= e(trans('nav.menu', [], $currentLocale)) ?>">
                    <span class="mnav-bloom-ring" aria-hidden="true"></span>
                    <span class="mnav-bloom-bars" aria-hidden="true"><i></i><i></i><i></i></span>
                </button>
            </div>
        </div>
<nav class="mnav-dropdown-bar" id="mnav-dropdown-bar" aria-label="<?= e(trans('nav.index', [], $currentLocale)) ?>">
            <div class="container mnav-dropdown-inner">
                <?php foreach ($indexLinks as $link): ?>
                    <a class="mnav-drop-link<?= $isActive($link['url']) ? ' is-active' : '' ?>"
                       href="<?= e($link['url']) ?>"
                       <?= str_starts_with($link['url'], 'http') ? 'target="_blank" rel="noopener"' : '' ?>
                       <?= $isActive($link['url']) ? 'aria-current="page"' : '' ?>><?= e(trans('nav.' . $link['key'], [], $currentLocale)) ?></a>
                <?php endforeach; ?>
                <a class="mnav-drop-link mnav-drop-catalog"
                   href="<?= e($catalogUrl) ?>"
                   target="_blank"
                   rel="noopener"><?= e(trans('nav.download_catalog', [], $currentLocale)) ?> (PDF)</a>
            </div>
        </nav>

        <span class="mnav-progress" aria-hidden="true"><i data-nav-progress></i></span>
    </div>
<div class="mnav-scrim" data-sheet-close aria-hidden="true"></div>

    <section class="mnav-sheet"
             id="mnav-sheet"
             data-nav-sheet
             role="dialog"
             aria-modal="true"
             aria-hidden="true"
             aria-label="<?= e(trans('nav.menu', [], $currentLocale)) ?>">
        <span class="mnav-sheet-bloom" aria-hidden="true">
            <span class="mnav-petal"></span>
            <span class="mnav-petal"></span>
            <span class="mnav-petal"></span>
        </span>

        <button type="button" class="mnav-sheet-handle" data-sheet-close aria-label="<?= e(trans('nav.close', [], $currentLocale)) ?>">
            <i aria-hidden="true"></i>
        </button>

        <div class="mnav-sheet-inner">
            <p class="mnav-sheet-eyebrow"><?= e(trans('nav.index', [], $currentLocale)) ?></p>

            <nav class="mnav-sheet-nav" aria-label="<?= e(trans('nav.menu', [], $currentLocale)) ?>">
                <?php foreach ($indexLinks as $position => $link): ?>
                    <a class="mnav-sheet-link<?= $isActive($link['url']) ? ' is-active' : '' ?>"
                       href="<?= e($link['url']) ?>"
                       <?= str_starts_with($link['url'], 'http') ? 'target="_blank" rel="noopener"' : '' ?>
                       <?= $isActive($link['url']) ? 'aria-current="page"' : '' ?>>
                        <span class="mnav-sheet-num"><?= e(str_pad((string) ($position + 1), 2, '0', STR_PAD_LEFT)) ?></span>
                        <span><?= e(trans('nav.' . $link['key'], [], $currentLocale)) ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="mnav-sheet-utils">
                <?php foreach ($languageLinks as $code => $link): ?>
                    <?php if ($code === $currentLocale): ?>
                        <?php ?>
                        <span class="mnav-util-link is-current"
                              aria-current="true"
                              lang="<?= e($code) ?>">
                            <span class="mnav-flag" aria-hidden="true"><?= $view->component('flag', ['code' => $code]) ?></span>
                            <?= e(strtoupper($code)) ?>
                        </span>
                    <?php else: ?>
                        <a class="mnav-util-link"
                           href="<?= e($link['target']) ?>"
                           lang="<?= e($code) ?>">
                            <span class="mnav-flag" aria-hidden="true"><?= $view->component('flag', ['code' => $code]) ?></span>
                            <?= e(strtoupper($code)) ?>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>

                <?php if ($favoritesOn): ?>
                    <a class="mnav-util-link" href="<?= e(route('favorites.index')) ?>">
                        <svg class="icon icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M12 20.6 4.2 12.8a5.1 5.1 0 0 1 0-7.2 5.1 5.1 0 0 1 7.2 0l.6.6.6-.6a5.1 5.1 0 0 1 7.2 0 5.1 5.1 0 0 1 0 7.2Z"></path>
                        </svg>
                        <?= e(trans('nav.favorites', [], $currentLocale)) ?>
                    </a>
                <?php endif; ?>

                <?php if ($plannerOn): ?>
                    <a class="mnav-util-link" href="<?= e(route('planner.index')) ?>">
                        <svg class="icon icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="3" y="4" width="18" height="16" rx="2"></rect>
                            <path d="M3 10h18M10 4v16"></path>
                        </svg>
                        <?= e(trans('nav.planner', [], $currentLocale)) ?>
                    </a>
                <?php endif; ?>

                <a class="mnav-util-link" href="<?= auth()->check() ? e(route('admin.dashboard')) : e(route('login')) ?>">
                    <?= e(trans('nav.account', [], $currentLocale)) ?>
                </a>

                <button type="button" class="mnav-util-link" data-theme-toggle>
                    <svg class="icon icon-sm" viewBox="0 0 24 24" aria-hidden="true">
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
                    <?= e(trans('nav.theme_toggle', [], $currentLocale)) ?>
                </button>

                <a class="mnav-util-link" href="<?= e($catalogUrl) ?>" target="_blank" rel="noopener">
                    <?= e(trans('nav.download_catalog', [], $currentLocale)) ?>
                </a>
            </div>
        </div>
    </section>
</header>
