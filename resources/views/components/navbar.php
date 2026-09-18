<?php
/**
 * Navbar — "Machined Glass".
 *
 * Row 1: brand mark · instant search · utility actions.
 * Row 2: blueprint rail — the crooked machined line on the inline-start edge,
 *        quick links, knurled meta strip and a scroll progress line.
 * Getting close to the crooked line unfolds the geometric index drawer.
 * Below 1024px the rail becomes a small bloom button that opens a bottom sheet.
 *
 * @var Core\View\View $view
 * @var Core\Localization\Translator $translator
 */

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

/* A link counts as current when its path matches — and, for catalogue links that
   differ only by query string, when the query matches too. */
$isActive = static function (string $target) use ($currentPath, $currentQuery): bool {
    $parts = parse_url($target) ?: [];
    $path = rtrim((string) ($parts['path'] ?? ''), '/');

    if ($path === '' || $path !== $currentPath) {
        return false;
    }

    $query = (string) ($parts['query'] ?? '');

    return $query === '' || $query === $currentQuery;
};

$indexLinks = [
    ['key' => 'bathroom', 'url' => route('products.index', ['category_id' => 1])],
    ['key' => 'kitchen', 'url' => route('products.index', ['category_id' => 4])],
    ['key' => 'latest', 'url' => route('products.index', ['sort' => 'newest'])],
    ['key' => 'collections', 'url' => route('products.index')],
    ['key' => 'finishes', 'url' => '#finishes'],
    ['key' => 'rituals', 'url' => '#rituals'],
    ['key' => 'inspirations', 'url' => '#inspiration'],
    ['key' => 'news', 'url' => '#corporate'],
    [
        'key' => 'contact',
        'url' => 'https://wa.me/908503040817?text=' . rawurlencode('Hello LUFLY, I would like to contact your architectural team.'),
    ],
];

$railLinks = array_slice($indexLinks, 0, 4);

$catalogUrl = 'https://wa.me/908503040817?text=' . rawurlencode('Hello LUFLY, please send the 2026 Master Technical PDF Catalog and BIM files.');

/* Language targets keep the current route translated per locale. */
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

        <!-- Row 1 — brand, search, utilities -->
        <div class="container mnav-top">
            <a class="mnav-brand" href="<?= e(route('home')) ?>" title="LUFLY Sanitary Architecture">
                <img class="mnav-brand-img"
                     src="<?= e(asset('frontend/design-system/logo.png')) ?>"
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
                            <a class="mnav-lang-option<?= $code === $currentLocale ? ' is-current' : '' ?>"
                               role="menuitem"
                               href="<?= e($link['target']) ?>"
                               lang="<?= e($code) ?>">
                                <span class="mnav-flag" aria-hidden="true"><?= $view->component('flag', ['code' => $code]) ?></span>
                                <span class="mnav-lang-name"><?= e($link['label']) ?></span>
                                <span class="mnav-lang-badge"><?= e(strtoupper($code)) ?></span>
                                <?php if ($code === $currentLocale): ?>
                                    <span class="mnav-lang-check" aria-hidden="true">&check;</span>
                                <?php endif; ?>
                            </a>
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

        <!-- The side spine — the whole second row, turned vertical -->
        <div class="mnav-rail">
            <div class="mnav-rail-inner">
                <button type="button"
                        class="mnav-mark"
                        data-drawer-toggle
                        aria-controls="mnav-drawer"
                        aria-expanded="false"
                        aria-haspopup="true"
                        aria-label="<?= e(trans('nav.open_index', [], $currentLocale)) ?>">
                    <span class="mnav-mark-label"><?= e(trans('nav.index', [], $currentLocale)) ?></span>
                    <span class="mnav-mark-draw" aria-hidden="true">
                        <svg class="mnav-mark-svg" viewBox="0 0 26 168" preserveAspectRatio="none" focusable="false">
                            <defs>
                                <linearGradient id="mnavDrawFade" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0" stop-color="currentColor" stop-opacity="0.95"></stop>
                                    <stop offset="0.58" stop-color="currentColor" stop-opacity="0.6"></stop>
                                    <stop offset="1" stop-color="currentColor" stop-opacity="0"></stop>
                                </linearGradient>
                            </defs>
                            <path class="mnav-mark-etch" d="M21 1 V23 L7 31 L23 41 L12 53 L12 65 L3 73 L18 85 L18 99 L10 107 V167"></path>
                            <path class="mnav-mark-etch mnav-mark-etch--thin" d="M25 1 V29 L14 36 L25 44 L19 55 L19 67 L11 75 L22 87 L22 98 L16 105 V167"></path>
                            <path class="mnav-mark-trace" d="M21 1 V23 L7 31 L23 41 L12 53 L12 65 L3 73 L18 85 L18 99 L10 107 V167"></path>
                        </svg>
                    </span>
                    <span class="mnav-mark-ticks" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i></span>
                </button>

                <nav class="mnav-quick" aria-label="<?= e(trans('nav.quick_links', [], $currentLocale)) ?>">
                    <?php foreach ($railLinks as $link): ?>
                        <a class="mnav-quick-link<?= $isActive($link['url']) ? ' is-active' : '' ?>"
                           href="<?= e($link['url']) ?>"
                           <?= $isActive($link['url']) ? 'aria-current="page"' : '' ?>><?= e(trans('nav.' . $link['key'], [], $currentLocale)) ?></a>
                    <?php endforeach; ?>
                </nav>

                <div class="mnav-rail-meta">
                    <span class="mnav-knurl" aria-hidden="true"></span>
                    <span class="mnav-rivet" aria-hidden="true"></span>
                    <a class="mnav-catalog"
                       href="<?= e($catalogUrl) ?>"
                       target="_blank"
                       rel="noopener"
                       title="<?= e(trans('nav.download_catalog', [], $currentLocale)) ?>">
                        <svg class="icon icon-sm" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7 10 12 15 17 10"></polyline>
                            <line x1="12" y1="15" x2="12" y2="3"></line>
                        </svg>
                        <span class="mnav-catalog-text"><?= e(trans('nav.download_catalog', [], $currentLocale)) ?></span>
                    </a>
                </div>
            </div>

            <!-- Index drawer — unfolded by the crooked line -->
            <aside class="mnav-drawer"
                   id="mnav-drawer"
                   data-drawer
                   aria-hidden="true"
                   aria-label="<?= e(trans('nav.index', [], $currentLocale)) ?>">
                <span class="mnav-drawer-knurl" aria-hidden="true"></span>
                <div class="mnav-drawer-head">
                    <span class="mnav-drawer-eyebrow"><?= e(trans('nav.index', [], $currentLocale)) ?></span>
                    <span class="mnav-drawer-rule" aria-hidden="true"></span>
                    <button type="button" class="mnav-drawer-close" data-drawer-close aria-label="<?= e(trans('nav.close', [], $currentLocale)) ?>">
                        <svg class="icon icon-sm" viewBox="0 0 24 24" aria-hidden="true">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>

                <nav class="mnav-index">
                    <?php foreach ($indexLinks as $position => $link): ?>
                        <a class="mnav-index-item<?= $isActive($link['url']) ? ' is-active' : '' ?>"
                           href="<?= e($link['url']) ?>"
                           <?= str_starts_with($link['url'], 'http') ? 'target="_blank" rel="noopener"' : '' ?>
                           <?= $isActive($link['url']) ? 'aria-current="page"' : '' ?>>
                            <span class="mnav-index-num"><?= e(str_pad((string) ($position + 1), 2, '0', STR_PAD_LEFT)) ?></span>
                            <span class="mnav-index-label"><?= e(trans('nav.' . $link['key'], [], $currentLocale)) ?></span>
                            <svg class="icon icon-sm mnav-index-arrow" viewBox="0 0 24 24" aria-hidden="true">
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                                <polyline points="12 5 19 12 12 19"></polyline>
                            </svg>
                        </a>
                    <?php endforeach; ?>
                </nav>

                <div class="mnav-drawer-foot">
                    <span class="mnav-drawer-note">LUFLY &middot; Sanitary Architecture</span>
                </div>
            </aside>

            <span class="mnav-progress" aria-hidden="true"><i data-nav-progress></i></span>
        </div>
    </div>

    <!-- Mobile bloom sheet -->
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
                    <a class="mnav-util-link<?= $code === $currentLocale ? ' is-current' : '' ?>"
                       href="<?= e($link['target']) ?>"
                       lang="<?= e($code) ?>">
                        <span class="mnav-flag" aria-hidden="true"><?= $view->component('flag', ['code' => $code]) ?></span>
                        <?= e(strtoupper($code)) ?>
                    </a>
                <?php endforeach; ?>

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
