<?php
/**
 * @var Core\View\View $view
 *
 * HERO CINEMA "Living Monolith": cinematic WebP showcase.
 * Slide 1 is the brand panel (masked logo reveal + travelling shine).
 * Trans keys: home.cinema1_* ... cinema5_* (en / ar / tr / cs).
 */
$view->pushStyle('frontend/home/hero-cinema/hero-cinema.css');
$view->pushScript('frontend/home/hero-cinema/hero-cinema.js');

/* The first slide is the largest paint: preload for instant LCP */
$view->pushPreload(asset('images/lifestyle/heroc-1.webp'), [
    'as' => 'image',
    'type' => 'image/webp',
    'media' => '(min-width: 761px)',
    'fetchpriority' => 'high',
]);
$view->pushPreload(asset('images/lifestyle/heroc-1-m.webp'), [
    'as' => 'image',
    'type' => 'image/webp',
    'media' => '(max-width: 760px)',
    'fetchpriority' => 'high',
]);

$logoUrl = asset('images/lifestyle/hero-logo.png');

$slides = [
    ['img' => 'heroc-1', 'brand' => true,
     'kicker' => 'cinema1_kicker', 'tag' => 'cinema1_tag', 'desc' => 'cinema1_desc',
     'alt' => 'LUFLY matte black basin mixer with flowing water'],
    ['img' => 'heroc-2',
     'kicker' => 'cinema2_kicker', 'ta' => 'cinema2_title_a', 'tb' => 'cinema2_title_b', 'desc' => 'cinema2_desc',
     'alt' => 'LUFLY concealed thermostatic rain shower'],
    ['img' => 'heroc-3',
     'kicker' => 'cinema3_kicker', 'ta' => 'cinema3_title_a', 'tb' => 'cinema3_title_b', 'desc' => 'cinema3_desc',
     'alt' => 'LUFLY brushed gold PVD faucet macro detail'],
    ['img' => 'heroc-4',
     'kicker' => 'cinema4_kicker', 'ta' => 'cinema4_title_a', 'tb' => 'cinema4_title_b', 'desc' => 'cinema4_desc',
     'alt' => 'LUFLY smart kitchen faucet with digital temperature display'],
    ['img' => 'heroc-5',
     'kicker' => 'cinema5_kicker', 'ta' => 'cinema5_title_a', 'tb' => 'cinema5_title_b', 'desc' => 'cinema5_desc',
     'alt' => 'LUFLY freestanding bath suite in a luxury penthouse bathroom'],
];

$waText = rawurlencode('Hello LUFLY, I would like to inquire about your collections and project pricing.');
?>
<section class="lfc" id="lfc" aria-label="LUFLY hero showcase">

    <!-- scene layer -->
    <div class="lfc-scenes">
        <?php foreach ($slides as $i => $s): ?>
            <div class="lfc-scene"
                 role="img" aria-label="<?= e($s['alt']) ?>"
                 data-img="<?= e(asset("images/lifestyle/{$s['img']}.webp")) ?>"
                 data-img-m="<?= e(asset("images/lifestyle/{$s['img']}-m.webp")) ?>"></div>
        <?php endforeach; ?>
    </div>

    <!-- atmosphere -->
    <div class="lfc-atmo" aria-hidden="true">
        <div class="lfc-shade"></div>
        <div class="lfc-glow"></div>
        <div class="lfc-vignette"></div>
        <div class="lfc-grain"></div>
        <div class="lfc-beam"></div>
    </div>

    <!-- copy panels -->
    <div class="lfc-stage">
        <?php foreach ($slides as $i => $s): ?>
            <div class="lfc-panel<?= !empty($s['brand']) ? ' lfc-panel--brand' : '' ?>">
                <div class="lfc-inner">
                    <div class="lfc-kicker"><span><?= e(trans('home.' . $s['kicker'])) ?></span></div>

                    <?php if (!empty($s['brand'])): ?>
                        <div class="lfc-logoBox">
                            <span class="lfc-corner tl"></span><span class="lfc-corner tr"></span>
                            <span class="lfc-corner bl"></span><span class="lfc-corner br"></span>
                            <div class="lfc-logoMask"><img class="lfc-logoImg" src="<?= e($logoUrl) ?>" alt="LUFLY" width="430" height="274" decoding="async"></div>
                            <div class="lfc-scan"></div>
                            <div class="lfc-shine" style="-webkit-mask-image:url('<?= e($logoUrl) ?>');mask-image:url('<?= e($logoUrl) ?>');-webkit-mask-size:contain;mask-size:contain;-webkit-mask-position:center;mask-position:center;-webkit-mask-repeat:no-repeat;mask-repeat:no-repeat;"></div>
                        </div>
                        <div class="lfc-tag"><?= e(trans('home.' . $s['tag'])) ?></div>
                        <p class="lfc-sub"><?= e(trans('home.' . $s['desc'])) ?></p>
                    <?php else: ?>
                        <h2 class="lfc-title">
                            <span class="lfc-line"><span><?= e(trans('home.' . $s['ta'])) ?></span></span>
                            <span class="lfc-line"><span><?= e(trans('home.' . $s['tb'])) ?></span></span>
                        </h2>
                        <div class="lfc-rule"></div>
                        <p class="lfc-sub"><?= e(trans('home.' . $s['desc'])) ?></p>
                    <?php endif; ?>

                    <div class="lfc-ctas">
                        <a href="<?= e(route('products.index')) ?>" class="lfc-cta lfc-cta--p">
                            <span><?= e(trans('home.cinema_primary')) ?></span>
                            <svg class="lfc-btn-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                                <polyline points="12 5 19 12 12 19"></polyline>
                            </svg>
                        </a>
                        <a href="https://wa.me/908503040817?text=<?= $waText ?>"
                           target="_blank" rel="noopener" class="lfc-cta lfc-cta--s">
                            <svg class="lfc-btn-icon" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0 0 12.04 2zm0 18.15c-1.48 0-2.93-.4-4.2-1.15l-.3-.18-3.12.82.83-3.04-.2-.31a8.19 8.19 0 0 1-1.26-4.38c0-4.54 3.7-8.24 8.24-8.24 2.2 0 4.27.86 5.82 2.42a8.18 8.18 0 0 1 2.41 5.83c0 4.54-3.7 8.23-8.24 8.23zm4.52-6.16c-.25-.12-1.47-.72-1.7-.81-.23-.08-.39-.12-.56.12-.17.25-.64.81-.79.97-.14.17-.29.19-.54.06-.25-.12-1.05-.39-2-1.23-.74-.66-1.24-1.47-1.39-1.72-.14-.25-.02-.38.11-.5.11-.11.25-.29.37-.43.12-.14.17-.25.25-.41.08-.17.04-.31-.02-.43s-.56-1.34-.76-1.84c-.2-.48-.41-.42-.56-.43h-.48c-.17 0-.44.06-.66.31-.23.25-.88.86-.88 2.09s.9 2.42 1.03 2.59c.12.17 1.77 2.7 4.29 3.78.6.26 1.07.41 1.43.53.6.19 1.15.16 1.58.1.48-.07 1.47-.6 1.68-1.18.21-.58.21-1.07.14-1.18-.06-.1-.23-.17-.48-.29z"/>
                            </svg>
                            <span><?= e(trans('home.whatsapp_specifier')) ?></span>
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- progress ticks -->
    <nav class="lfc-progress" aria-label="Slides">
        <?php foreach ($slides as $i => $s): ?>
            <button type="button" class="lfc-tick" aria-label="Slide <?= $i + 1 ?>">
                <span class="lfc-num">0<?= $i + 1 ?></span>
                <span class="lfc-bar"><i></i></span>
            </button>
        <?php endforeach; ?>
    </nav>

    <!-- arrows -->
    <div class="lfc-arrows">
        <button type="button" class="lfc-arrow" id="lfc-prev" aria-label="Previous slide">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </button>
        <button type="button" class="lfc-arrow" id="lfc-next" aria-label="Next slide">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <polyline points="9 18 15 12 9 6"></polyline>
            </svg>
        </button>
    </div>
</section>
