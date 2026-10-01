<?php
/**
 * Navbar: "Overlay Glass".
 *
 * One 64px row. On the storefront home page the hero photograph runs under
 * it (the hero pulls itself up under this sticky bar), so the bar is
 * transparent with white ink while the hero is on screen and dissolves into
 * theme glass as the hero scrolls away. Everywhere else it is glass from
 * the first paint.
 *
 * Row: Lufly logo (inline-start) - centre index links (desktop, glass state
 * only) - the expanding search field - theme toggle | search | box, wishlist,
 * account | language switcher (inline-end), thin dividers between groups.
 * Below 1024px the links fold into the bloom sheet. Saved list and Box live
 * only in the top bar (icon buttons), never repeated in the sheet.
 * On the home hero the search field is presented open at the centre of the
 * bar (navbar.js + the is-hero-field rules, desktop only) while the bar is
 * part of the photograph - a deep readable pill over the artwork - and it
 * folds in place at the centre line as the glass takes over.
 *
 * Contracts kept for other scripts:
 *   [data-theme-toggle]      app.js swaps the theme
 *   [data-fav-count]         favorites.js repaints the badge
 *   [data-box-count]         box.js repaints the badge
 *   .mnav-fav / .mnav-box    has-items state for both
 *   --mnav-row1              pages subtract the row height from 100svh
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

/* The hero runs under the navbar only on the storefront home page. */
$isOverlay = $route !== null && ($route->name ?? '') === 'home';

/* A link counts as current when its path matches - and, for catalogue links
   that differ only by query string, when the query matches too. */
$isActive = static function (string $target) use ($currentPath, $currentQuery): bool {
    $parts = parse_url($target) ?: [];
    $path = rtrim((string) ($parts['path'] ?? ''), '/');

    if ($path === '' || $path !== $currentPath) {
        return false;
    }

    $query = (string) ($parts['query'] ?? '');

    return $query === '' || $query === $currentQuery;
};

/* Saved-products list: the badge shows how many products this visitor saved.
   Without the cookie the service answers 0 without touching the database. */
$favoritesOn = feature('favorites', true) && class_exists(\Modules\Favorites\Services\FavoriteService::class);
$savedCount = 0;
if ($favoritesOn) {
    $savedCount = app(\Modules\Favorites\Services\FavoriteService::class)->count();
}

/* Quotation box: the list the visitor fills to ask for every price at once. */
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
    ['key' => 'contact', 'url' => route('contact')],
];

$exploreLinks = [
    ['key' => 'finishes', 'url' => route('home') . '#finishes'],
    ['key' => 'rituals', 'url' => route('home') . '#rituals'],
    ['key' => 'inspirations', 'url' => route('home') . '#inspiration'],
    ['key' => 'news', 'url' => route('home') . '#corporate'],
];

/* every link, for the mobile sheet */
$sheetLinks = array_merge(
    [$indexLinks[0], $indexLinks[1], $indexLinks[2], $indexLinks[3]],
    $exploreLinks,
    [$indexLinks[4]]
);

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
<header class="mnav<?= $isOverlay ? ' mnav--overlay' : '' ?>" data-navbar<?= $isOverlay ? ' data-nav-hero' : '' ?><?= $isOverlay ? ' style="--nav-glass:0"' : '' ?>>

    <div class="mnav-slab">
        <!-- the glass layer: dark over the hero, theme glass afterwards.
             Its opacity is driven by --nav-glass (see navbar.js). -->
        <span class="mnav-glass" aria-hidden="true"></span>

        <!-- Row: brand / centre links / search / actions -->
        <div class="container mnav-row">

            <a class="mnav-brand" href="<?= e(route('home')) ?>" title="LUFLY Sanitary Architecture">
                <img class="mnav-brand-img"
                     src="<?= e(asset('images/logo.png')) ?>"
                     alt="LUFLY"
                     width="86"
                     height="55"
                     decoding="async"
                     fetchpriority="high">
            </a>

            <!-- centre index links: desktop + glass state only -->
            <nav class="mnav-links" aria-label="<?= e(trans('nav.index', [], $currentLocale)) ?>">
                <?php foreach ($indexLinks as $link): ?>
                    <a class="mnav-link<?= $isActive($link['url']) ? ' is-active' : '' ?>"
                       href="<?= e($link['url']) ?>"
                       <?= external_link_attrs($link['url']) ?>
                       <?= $isActive($link['url']) ? 'aria-current="page"' : '' ?>><?= e(trans('nav.' . $link['key'], [], $currentLocale)) ?></a>
                <?php endforeach; ?>
            </nav>

            <!-- expanding search: a field that grows out of the search icon -->
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
                           <?php $searchMoods = []; ?>
                           <?php for ($i = 1; $i <= 4; $i++): ?>
                               <?php $moodLine = trans('nav.search_mood_' . $i, [], $currentLocale); ?>
                               <?php if ($moodLine !== '' && strpos($moodLine, 'search_mood_') === false) $searchMoods[] = $moodLine; ?>
                           <?php endfor; ?>
                           <?php if ($searchMoods !== []): ?>
                               data-search-moods="<?= e((string) json_encode($searchMoods, JSON_UNESCAPED_UNICODE)) ?>"
                           <?php endif; ?>
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

                <span class="mnav-sep mnav-sep--search" aria-hidden="true"></span>

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

                <span class="mnav-sep" aria-hidden="true"></span>

                <?php if ($boxOn): ?>
                    <a href="<?= e(route('box.index')) ?>"
                       class="mnav-icon-btn mnav-box<?= $boxCount > 0 ? ' has-items' : '' ?>"
                       title="<?= e(trans('nav.box', [], $currentLocale)) ?>"
                       aria-label="<?= e(trans('nav.box', [], $currentLocale)) ?>">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
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
        <button type="button" class="mnav-sheet-handle" data-sheet-close aria-label="<?= e(trans('nav.close', [], $currentLocale)) ?>">
            <i aria-hidden="true"></i>
        </button>

        <div class="mnav-sheet-inner">
            <p class="mnav-sheet-eyebrow"><?= e(trans('nav.index', [], $currentLocale)) ?></p>

            <nav class="mnav-sheet-nav" aria-label="<?= e(trans('nav.menu', [], $currentLocale)) ?>">
                <?php foreach ($sheetLinks as $position => $link): ?>
                    <a class="mnav-sheet-link<?= $isActive($link['url']) ? ' is-active' : '' ?>"
                       href="<?= e($link['url']) ?>"
                       <?= external_link_attrs($link['url']) ?>
                       <?= $isActive($link['url']) ? 'aria-current="page"' : '' ?>>
                        <span class="mnav-sheet-num"><?= e(str_pad((string) ($position + 1), 2, '0', STR_PAD_LEFT)) ?></span>
                        <span><?= e(trans('nav.' . $link['key'], [], $currentLocale)) ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="mnav-sheet-utils">
                <button type="button" class="mnav-util-link" data-search-toggle>
                    <svg class="icon icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <?= e(trans('nav.search', [], $currentLocale)) ?>
                </button>

                <?php foreach ($languageLinks as $code => $link): ?>
                    <?php if ($code === $currentLocale): ?>
                        <span class="mnav-util-link is-current" aria-current="true" lang="<?= e($code) ?>">
                            <span class="mnav-flag" aria-hidden="true"><?= $view->component('flag', ['code' => $code]) ?></span>
                            <?= e(strtoupper($code)) ?>
                        </span>
                    <?php else: ?>
                        <a class="mnav-util-link" href="<?= e($link['target']) ?>" lang="<?= e($code) ?>">
                            <span class="mnav-flag" aria-hidden="true"><?= $view->component('flag', ['code' => $code]) ?></span>
                            <?= e(strtoupper($code)) ?>
                        </a>
                    <?php endif; ?>
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
            </div>
        </div>
    </section>
</header>
