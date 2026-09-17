<?php
/**
 * @var Core\View\View $view
 *
 * HERO CINEMA "Living Monolith" — cinematic WebP showcase that replaces
 * the heavy video hero (~530 KB of images vs ~12 MB of MP4s).
 * Slide 1 is the brand panel (masked logo reveal + travelling shine).
 * Trans keys: home.cinema1_* … cinema5_* (en / tr / cs).
 */
$view->pushStyle('frontend/home/hero-cinema/hero-cinema.css');
$view->pushScript('frontend/home/hero-cinema/hero-cinema.js');

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
                            <div class="lfc-logoMask"><img class="lfc-logoImg" src="<?= e($logoUrl) ?>" alt="LUFLY"></div>
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
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                        <a href="https://wa.me/908503040817?text=<?= $waText ?>"
                           target="_blank" rel="noopener" class="lfc-cta lfc-cta--s">
                            <i class="fa-brands fa-whatsapp"></i>
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
            <i class="fa-solid fa-chevron-left"></i>
        </button>
        <button type="button" class="lfc-arrow" id="lfc-next" aria-label="Next slide">
            <i class="fa-solid fa-chevron-right"></i>
        </button>
    </div>

    <!-- stats marquee -->
    <div class="lfc-ticker" aria-hidden="true">
        <div class="lfc-ticker-track">
            <?php for ($pass = 0; $pass < 2; $pass++): ?>
                <span class="lfc-ticker-item"><i class="fa-solid fa-industry"></i><?= e(trans('home.ticker_1')) ?></span>
                <span class="lfc-ticker-sep"></span>
                <span class="lfc-ticker-item"><i class="fa-solid fa-earth-americas"></i><?= e(trans('home.ticker_2')) ?></span>
                <span class="lfc-ticker-sep"></span>
                <span class="lfc-ticker-item"><i class="fa-solid fa-droplet"></i><?= e(trans('home.ticker_3')) ?></span>
                <span class="lfc-ticker-sep"></span>
                <span class="lfc-ticker-item"><i class="fa-solid fa-shield-halved"></i><?= e(trans('home.ticker_4')) ?></span>
                <span class="lfc-ticker-sep"></span>
                <span class="lfc-ticker-item"><i class="fa-solid fa-gem"></i><?= e(trans('home.ticker_5')) ?></span>
                <span class="lfc-ticker-sep"></span>
            <?php endfor; ?>
        </div>
    </div>
</section>
