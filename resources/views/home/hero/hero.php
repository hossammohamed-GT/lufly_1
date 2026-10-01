<?php
/**
 * @var Core\View\View $view
 *
 * HERO "Crafting Water" - cinematic full-bleed category showcase.
 *
 * One 100vh photograph runs under the navbar (the navbar is transparent while
 * the hero is on screen). Everything floats above the photo: the copy column
 * on the left, the scroll cue, the scene progress dots and the trust strip.
 * The four scenes slide in from the side (hero.js), while the active dot
 * contracts until the next scene takes over.
 *
 * Trans keys: home.hero_* (en / tr / cs).
 */
$view->pushStyle('frontend/home/hero/hero.css');
$view->pushScript('frontend/home/hero/hero.js');

/* The artwork is cached for a year: bump this with every image re-shoot so
   returning visitors actually receive the new files. */
$heroImgV = '2026-10-01.3';

/* LCP: the first scene is the largest paint. One master-quality lossless
   file serves every screen - no rendition ladder, no reduced variants. */
$firstImg = 'hero-bathroom';
$view->pushPreload(asset('images/hero/' . $firstImg . '.webp') . '?v=' . $heroImgV, [
    'as' => 'image',
    'type' => 'image/webp',
    'fetchpriority' => 'high',
]);

/* The four core worlds, in autoplay order. The hero focuses on fittings,
   mirrors and installed hardware rather than ceramic products. */
$heroScenes = [
    [
        'key' => 'bathroom', 'label' => 'hero_tab_bathroom',
        'url' => route('products.index', ['category' => 'washbasin-mixers']),
        'alt' => 'Luxury bathroom fittings with a sculptural basin mixer and mirror',
    ],
    [
        'key' => 'kitchen', 'label' => 'hero_tab_kitchen',
        'url' => route('products.index', ['category' => 'sink-mixers']),
        'alt' => 'Luxury kitchen with a sculptural pull-down mixer and sink hardware',
    ],
    [
        'key' => 'handicap', 'label' => 'hero_tab_accessible',
        'url' => route('products.index', ['category' => 'accessible-range']),
        'alt' => 'Elegant accessible bathroom with refined grab rails and comfortable fittings',
    ],
    [
        'key' => 'kids', 'label' => 'hero_tab_kids',
        'url' => route('products.index', ['category' => 'kids']),
        'alt' => 'Premium child-friendly bathroom fittings with a colorful mixer and mirror',
    ],
];

?>

<section class="hero" id="lufly-hero" data-hero aria-label="<?= e(trans('home.hero_tabs_label')) ?>">

    <!-- Scene stack: every frame is in the DOM; the engine hands scenes off
         from the side without exposing a blank frame. Only the first one ships
         a real src - the rest is promoted one by one after the first paint,
         so the visit never waits for four photos. -->
    <div class="hero-media" aria-hidden="true">
        <?php foreach ($heroScenes as $i => $s): ?>
            <?php if ($i === 0): ?>
                <img class="hero-frame is-active"
                     src="<?= e(asset('images/hero/hero-' . $s['key'] . '.webp') . '?v=' . $heroImgV) ?>"
                     data-hero-src="<?= e(asset('images/hero/hero-' . $s['key'] . '.webp') . '?v=' . $heroImgV) ?>"
                     alt=""
                     width="4300" height="2400"
                     fetchpriority="high"
                     decoding="async">
            <?php else: ?>
                <img class="hero-frame"
                     data-hero-src="<?= e(asset('images/hero/hero-' . $s['key'] . '.webp') . '?v=' . $heroImgV) ?>"
                     alt=""
                     width="4300" height="2400"
                     decoding="async">
            <?php endif; ?>
        <?php endforeach; ?>

        <!-- readability gradients: navbar strip on top, copy on the start
             side, tabs + trust strip along the bottom -->
        <div class="hero-shade"></div>
    </div>

    <!-- Copy column -->
    <div class="hero-body">
        <div class="hero-copy">
            <p class="hero-kicker">
                <span class="hero-kicker-line" aria-hidden="true"></span>
                <span class="hero-kicker-text"><?= e(trans('home.hero_kicker')) ?></span>
            </p>

            <h1 class="hero-title">
                <span class="hero-title-row"><span class="hero-title-word"><?= e(trans('home.hero_title_1')) ?></span></span>
                <span class="hero-title-row"><span class="hero-title-word"><?= e(trans('home.hero_title_2')) ?></span></span>
                <span class="hero-title-row hero-title-row--accent"><span class="hero-title-word"><?= e(trans('home.hero_title_3')) ?></span></span>
                <span class="hero-title-row hero-title-row--accent"><span class="hero-title-word"><?= e(trans('home.hero_title_4')) ?></span></span>
            </h1>

            <p class="hero-desc"><?= e(trans('home.hero_desc')) ?></p>

            <div class="hero-ctas">
                <a href="<?= e(route('products.index')) ?>" class="hero-cta hero-cta--primary">
                    <span class="hero-cta-label"><?= e(trans('home.hero_cta_primary')) ?></span>
                    <span class="hero-cta-arrow" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
                    </span>
                </a>
                <a href="#rituals" class="hero-cta hero-cta--story" data-hero-story>
                    <span class="hero-cta-play" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5.5v13l11-6.5-11-6.5Z"/></svg>
                    </span>
                    <span class="hero-cta-story-text"><?= e(trans('home.hero_cta_story')) ?></span>
                </a>
            </div>
        </div>
    </div>

    <!-- Scroll cue -->
    <a class="hero-scroll" href="#engineering-trust" data-hero-scroll>
        <span class="hero-scroll-mouse" aria-hidden="true"><i class="hero-scroll-wheel"></i></span>
        <span class="hero-scroll-text"><?= e(trans('home.hero_scroll')) ?></span>
    </a>

    <!-- Scene selector: four compact progress dots replace the thumbnail cards.
         The active dot stretches into a line, then contracts until the next
         scene enters from the side. The labels stay available to screen
         readers and keyboard users. -->
    <nav class="hero-tabs" data-hero-tabs aria-label="<?= e(trans('home.hero_tabs_label')) ?>">
        <?php foreach ($heroScenes as $i => $s): ?>
            <div class="hero-tab<?= $i === 0 ? ' is-active' : '' ?>" data-hero-panel="<?= (int) $i ?>">
                <button type="button"
                        class="hero-tab-switch"
                        data-hero-tab="<?= (int) $i ?>"
                        aria-pressed="<?= $i === 0 ? 'true' : 'false' ?>"
                        aria-label="<?= e(trans('home.' . $s['label'])) ?>"
                        title="<?= e(trans('home.' . $s['label'])) ?>">
                    <span class="hero-tab-tick" aria-hidden="true"></span>
                    <span class="visually-hidden"><?= e(trans('home.' . $s['label'])) ?></span>
                </button>
            </div>
        <?php endforeach; ?>
    </nav>

    <!-- Trust strip -->
    <div class="hero-trust" aria-label="LUFLY">
        <div class="hero-trust-item">
            <span class="hero-trust-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2.7 20 9l-3 11.5H7L4 9l8-6.3Z"/><path d="M9.5 9.5 12 12l2.5-2.5"/></svg>
            </span>
            <span class="hero-trust-text">
                <strong><?= e(trans('home.hero_trust_1_title')) ?></strong>
                <small><?= e(trans('home.hero_trust_1_sub')) ?></small>
            </span>
        </div>
        <div class="hero-trust-item">
            <span class="hero-trust-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3.2"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.87l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.7 1.7 0 0 0-1.87-.34 1.7 1.7 0 0 0-1.03 1.56V21a2 2 0 1 1-4 0v-.09a1.7 1.7 0 0 0-1.11-1.56 1.7 1.7 0 0 0-1.87.34l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.7 1.7 0 0 0 .34-1.87 1.7 1.7 0 0 0-1.56-1.03H3a2 2 0 1 1 0-4h.09a1.7 1.7 0 0 0 1.56-1.11 1.7 1.7 0 0 0-.34-1.87l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.7 1.7 0 0 0 1.87.34h.01a1.7 1.7 0 0 0 1.03-1.56V3a2 2 0 1 1 4 0v.09a1.7 1.7 0 0 0 1.03 1.56 1.7 1.7 0 0 0 1.87-.34l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.7 1.7 0 0 0-.34 1.87v.01a1.7 1.7 0 0 0 1.56 1.03H21a2 2 0 1 1 0 4h-.09a1.7 1.7 0 0 0-1.56 1.03Z"/></svg>
            </span>
            <span class="hero-trust-text">
                <strong><?= e(trans('home.hero_trust_2_title')) ?></strong>
                <small><?= e(trans('home.hero_trust_2_sub')) ?></small>
            </span>
        </div>
        <div class="hero-trust-item">
            <span class="hero-trust-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3c-2.4 3.2-6.5 7-6.5 11a6.5 6.5 0 0 0 13 0C18.5 10 14.4 6.2 12 3Z"/><path d="M9.5 14.5c-.4-1.6.3-3.2 1.6-4.2"/></svg>
            </span>
            <span class="hero-trust-text">
                <strong><?= e(trans('home.hero_trust_3_title')) ?></strong>
                <small><?= e(trans('home.hero_trust_3_sub')) ?></small>
            </span>
        </div>
        <div class="hero-trust-item">
            <span class="hero-trust-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 20 6.2v5.3c0 4.9-3.4 8.6-8 10.5-4.6-1.9-8-5.6-8-10.5V6.2L12 3Z"/><path d="m8.8 12 2.2 2.2 4.2-4.7"/></svg>
            </span>
            <span class="hero-trust-text">
                <strong><?= e(trans('home.hero_trust_4_title')) ?></strong>
                <small><?= e(trans('home.hero_trust_4_sub')) ?></small>
            </span>
        </div>
    </div>
</section>
