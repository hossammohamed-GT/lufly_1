<?php
/**
 * Home component: the finishes lab.
 *
 * Staged after the owner's reference: the whole band is ONE warm-lit
 * bathroom scene, and the faucet in that scene IS the finish preview.
 * Selecting a card cross-fades the scene photograph into the same bathroom
 * with the faucet wearing the chosen finish (600ms, two stacked layers), so
 * the product itself tells the customer what they picked - no colour chips,
 * no macro textures. The left column carries the headline, the description,
 * the horizontally scrolling finish rail (round prev/next chevrons) and the
 * CTAs. The finish's name and story ride the scene as a white callout next
 * to the faucet, the interactive hotspots sit on the faucet itself, and the
 * spec sheet floats as a glass card beside the product. Every text that
 * changes per finish is a translation key; the per-finish copy rides on the
 * cards as data attributes so the script never hardcodes copy.
 *
 * Finish list confirmed with the factory (management review): every finish
 * below is currently available, "Brushed Steel" is renamed to "Brushed
 * Nickel", Mirror Gold plus the Gunmetal variants (Brushed Gunmetal / Gun
 * Gray) were added, and the PVD badge is shown only where PVD is used.
 */

$view->pushDeferredStyle('frontend/home/finishes/finishes.css');
$view->pushScript('frontend/home/finishes/finishes.js');

/* Per-finish presentation data. phrase/story are translation keys; rates
   drive the comparison drawer (durability / fingerprints / luxury). */
$finishMeta = [
    'brushed-rose-gold'  => ['phrase' => 'finishes_phrase_brushed_rose_gold',  'story' => 'finishes_story_brushed_rose_gold',  'rates' => ['high', 'low', 'high']],
    'chrome'             => ['phrase' => 'finishes_phrase_chrome',             'story' => 'finishes_story_chrome',             'rates' => ['medium', 'high', 'medium']],
    'brushed-gold'       => ['phrase' => 'finishes_phrase_brushed_gold',       'story' => 'finishes_story_brushed_gold',       'rates' => ['high', 'low', 'high']],
    'mirror-gold'        => ['phrase' => 'finishes_phrase_mirror_gold',        'story' => 'finishes_story_mirror_gold',        'rates' => ['medium', 'high', 'high']],
    'matte-black'        => ['phrase' => 'finishes_phrase_matte_black',        'story' => 'finishes_story_matte_black',        'rates' => ['high', 'medium', 'high']],
    'brushed-nickel'     => ['phrase' => 'finishes_phrase_brushed_nickel',     'story' => 'finishes_story_brushed_nickel',     'rates' => ['high', 'low', 'medium']],
    'gunmetal'           => ['phrase' => 'finishes_phrase_gunmetal',           'story' => 'finishes_story_gunmetal',           'rates' => ['high', 'medium', 'high']],
    'brushed-gunmetal'   => ['phrase' => 'finishes_phrase_brushed_gunmetal',   'story' => 'finishes_story_brushed_gunmetal',   'rates' => ['high', 'low', 'high']],
    'gun-gray'           => ['phrase' => 'finishes_phrase_gun_gray',           'story' => 'finishes_story_gun_gray',           'rates' => ['high', 'medium', 'medium']],
];

/* The rail the customer picks from. sphere is the material ball on the
   card; scene is the full-bleed photograph the band cross-fades into. */
$finishes = [
    ['key' => 'brushed-rose-gold', 'sphere' => 'images/finishes/sphere-brushed-rose-gold.jpg',  'scene' => 'images/finishes/scene-brushed-rose-gold.jpg',  'label' => 'Rose Gold',       'tech' => 'PVD'],
    ['key' => 'chrome',            'sphere' => 'images/finishes/sphere-chrome.jpg',             'scene' => 'images/finishes/scene-chrome.jpg',             'label' => 'Chrome',          'tech' => 'Electroplated'],
    ['key' => 'brushed-gold',      'sphere' => 'images/finishes/sphere-brushed-gold.jpg',       'scene' => 'images/finishes/scene-brushed-gold.jpg',       'label' => 'Brushed Gold',    'tech' => 'PVD'],
    ['key' => 'mirror-gold',       'sphere' => 'images/finishes/sphere-mirror-gold.jpg',        'scene' => 'images/finishes/scene-mirror-gold.jpg',        'label' => 'Mirror Gold',     'tech' => 'PVD'],
    ['key' => 'matte-black',       'sphere' => 'images/finishes/sphere-matte-black.jpg',        'scene' => 'images/finishes/scene-matte-black.jpg',        'label' => 'Matte Black',     'tech' => 'Powder Coat'],
    ['key' => 'brushed-nickel',    'sphere' => 'images/finishes/sphere-brushed-nickel.jpg',     'scene' => 'images/finishes/scene-brushed-nickel.jpg',     'label' => 'Brushed Nickel',  'tech' => 'PVD'],
    ['key' => 'gunmetal',          'sphere' => 'images/finishes/sphere-gunmetal.jpg',           'scene' => 'images/finishes/scene-gunmetal.jpg',           'label' => 'Gunmetal',        'tech' => 'PVD'],
    ['key' => 'brushed-gunmetal',  'sphere' => 'images/finishes/sphere-brushed-gunmetal.jpg',   'scene' => 'images/finishes/scene-brushed-gunmetal.jpg',   'label' => 'Brushed Gunmetal', 'tech' => 'PVD'],
    ['key' => 'gun-gray',          'sphere' => 'images/finishes/sphere-gun-gray.jpg',           'scene' => 'images/finishes/scene-gun-gray.jpg',           'label' => 'Gun Gray',        'tech' => 'PVD'],
];

$first = $finishes[0]['key'];
$firstMeta = $finishMeta[$first];
$rateWord = static fn (string $r): string => trans('home.finishes_rating_' . $r);
?>
<section class="band finishes-section scroll-section" id="finishes" data-finish="<?= e($first) ?>">

    <!-- ============ the stage IS the band: one bathroom scene, the faucet
                 wears the selected finish (cross-fades, finishes.js) ====== -->
    <div class="fs-scene" aria-hidden="true">
        <?= $view->component('responsive-image', [
            'src'    => $finishes[0]['scene'],
            'alt'    => '',
            'class'  => 'fs-scene-img fs-scene-img-a is-on',
            'width'  => 1376,
            'height' => 768,
            'sizes'  => '100vw',
            'widths' => [480, 960, 1280],
        ]) ?>
        <?= $view->component('responsive-image', [
            'src'    => $finishes[0]['scene'],
            'alt'    => '',
            'class'  => 'fs-scene-img fs-scene-img-b',
            'width'  => 1376,
            'height' => 768,
            'sizes'  => '100vw',
            'widths' => [480, 960, 1280],
        ]) ?>
        <div class="fs-scene-shade"></div>
    </div>

    <!-- ============ overlays that track the faucet through every crop =====
             (data-x / data-y are percentages of the photograph; finishes.js
             maps them onto the rendered box, so they survive any crop) ===== -->
    <div class="fs-scene-ui">
        <div class="fs-callout" data-x="54" data-y="13">
            <span class="fs-callout-rule" aria-hidden="true"></span>
            <b id="fs-caption-title"><?= e(trans('home.finish_default_title')) ?></b>
            <span id="fs-story"><?= e(trans('home.' . $firstMeta['story'])) ?></span>
        </div>

        <button type="button" class="fs-hotspot" data-spot="pvd" data-x="81" data-y="35">
            <span class="fs-spot-dot" aria-hidden="true"></span>
            <span class="fs-spot-tip">
                <b><?= e(trans('home.finishes_hs_pvd_t')) ?></b>
                <i><?= e(trans('home.finishes_hs_pvd_d')) ?></i>
            </span>
        </button>
        <button type="button" class="fs-hotspot" data-spot="core" data-x="75" data-y="66">
            <span class="fs-spot-dot" aria-hidden="true"></span>
            <span class="fs-spot-tip">
                <b><?= e(trans('home.finishes_hs_core_t')) ?></b>
                <i><?= e(trans('home.finishes_hs_core_d')) ?></i>
            </span>
        </button>
        <button type="button" class="fs-hotspot" data-spot="aerator" data-x="63" data-y="46">
            <span class="fs-spot-dot" aria-hidden="true"></span>
            <span class="fs-spot-tip">
                <b><?= e(trans('home.finishes_hs_aerator_t')) ?></b>
                <i><?= e(trans('home.finishes_hs_aerator_d')) ?></i>
            </span>
        </button>
    </div>

    <div class="container finishes-container">
        <div class="fs-grid">

            <!-- ============ LEFT: headline, description, rail, CTAs ============ -->
            <div class="fs-left">
                <span class="section-tag fs-anim" style="--d: 0"><?= e(trans('home.finishes_kicker')) ?></span>

                <h2 class="fs-headline fs-anim" style="--d: 1">
                    <?= e(trans('home.finishes_headline_lead')) ?><br>
                    <em class="fs-phrase" id="fs-phrase"><?= e(trans('home.' . $firstMeta['phrase'])) ?></em>
                </h2>

                <p class="fs-desc fs-anim" id="finish-desc" style="--d: 2"><?= e(trans('home.finish_default_desc')) ?></p>

                <!-- the finish rail: horizontal scroll, round chevrons on both
                     sides. The arrows only scroll the rail; the cards select. -->
                <div class="fs-carousel fs-anim" style="--d: 3">
                    <button type="button" class="fs-nav" data-fs-prev aria-label="<?= e(trans('home.finishes_nav_prev')) ?>">
                        <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M15 5l-7 7 7 7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>

                    <div class="fs-cards" role="group" aria-label="<?= e(trans('home.finishes_kicker')) ?>">
                        <?php foreach ($finishes as $i => $finish):
                            $meta = $finishMeta[$finish['key']] ?? null;
                            if ($meta === null) { continue; }
                        ?>
                            <button type="button"
                                    class="fs-card<?= $i === 0 ? ' is-active' : '' ?>"
                                    data-finish="<?= e($finish['key']) ?>"
                                    data-phrase="<?= e(trans('home.' . $meta['phrase'])) ?>"
                                    data-story="<?= e(trans('home.' . $meta['story'])) ?>"
                                    aria-pressed="<?= $i === 0 ? 'true' : 'false' ?>"
                                    style="--i: <?= (int) $i ?>">
                                <span class="fs-card-chip">
                                    <img src="<?= e(asset($finish['sphere'])) ?>" alt="" width="84" height="84" loading="lazy" decoding="async">
                                </span>
                                <span class="fs-card-text">
                                    <b class="fs-card-name"><?= e($finish['label']) ?></b>
                                    <small class="fs-card-tech"><?= e($finish['tech']) ?></small>
                                </span>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <button type="button" class="fs-nav" data-fs-next aria-label="<?= e(trans('home.finishes_nav_next')) ?>">
                        <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M9 5l7 7-7 7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                </div>

                <div class="fs-ctas fs-anim" style="--d: 4">
                    <a href="<?= e(route('products.index')) ?>" class="fs-cta fs-cta-main">
                        <span><?= e(trans('home.finishes_explore')) ?></span>
                        <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="M4 12h15m0 0l-6-6m6 6l-6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </a>
                    <button type="button" class="fs-cta fs-cta-ghost" data-fs-compare>
                        <span><?= e(trans('home.finishes_compare')) ?></span>
                    </button>
                </div>
            </div>

            <!-- ============ RIGHT: the spec sheet beside the product ============ -->
            <div class="fs-right">
                <div class="fs-specs fs-anim" style="--d: 3">
                    <div class="fs-specs-head">
                        <h3 class="finish-title" id="finish-title"><?= e(trans('home.finish_default_title')) ?></h3>
                        <span class="finish-counter"><b id="finish-index">01</b> / <?= e(str_pad((string) count($finishes), 2, '0', STR_PAD_LEFT)) ?></span>
                    </div>

                    <span class="finish-spec-pill" id="finish-tag">PVD TITANIUM VAPOR DEPOSITION</span>

                    <div class="finish-tech-specs">
                        <div class="tech-spec-item">
                            <span><?= e(trans('home.finish_base_label')) ?></span>
                            <b id="finish-base">Solid Brass CW617N</b>
                        </div>
                        <div class="tech-spec-item">
                            <span><?= e(trans('home.finish_coating_label')) ?></span>
                            <b id="finish-coating">PVD Titanium 0.4µm</b>
                        </div>
                        <div class="tech-spec-item">
                            <span><?= e(trans('home.finish_cartridge_label')) ?></span>
                            <b id="finish-cartridge">Kerox® Hungary 35mm Ceramic</b>
                        </div>
                        <div class="tech-spec-item">
                            <span><?= e(trans('home.finish_aerator_label')) ?></span>
                            <b id="finish-aerator">Neoperl® Coin-Slot Pro-Eco 5.7 L/min</b>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============ the factory strip ============ -->
        <div class="fs-strip fs-anim" style="--d: 5">
            <span><?= e(trans('home.finishes_strip_finishes', ['count' => count($finishes)])) ?></span>
            <span><?= e(trans('home.finishes_strip_durability')) ?></span>
            <span><?= e(trans('home.finishes_strip_pvd')) ?></span>
            <span><?= e(trans('home.finishes_strip_warranty')) ?></span>
        </div>
    </div>

    <!-- ============ compare drawer ============ -->
    <div class="fs-drawer" data-fs-drawer hidden>
        <div class="fs-drawer-panel" role="dialog" aria-modal="true" aria-label="<?= e(trans('home.finishes_compare')) ?>">
            <div class="fs-drawer-head">
                <b><?= e(trans('home.finishes_compare_title')) ?></b>
                <button type="button" class="fs-drawer-close" data-fs-compare-close aria-label="<?= e(trans('home.finishes_compare_close')) ?>">×</button>
            </div>
            <div class="fs-drawer-table">
                <table>
                    <thead>
                        <tr>
                            <th><?= e(trans('home.finishes_col_finish')) ?></th>
                            <th><?= e(trans('home.finishes_col_durability')) ?></th>
                            <th><?= e(trans('home.finishes_col_fingerprints')) ?></th>
                            <th><?= e(trans('home.finishes_col_luxury')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($finishes as $finish):
                            $meta = $finishMeta[$finish['key']] ?? null;
                            if ($meta === null) { continue; }
                        ?>
                            <tr data-finish-row="<?= e($finish['key']) ?>">
                                <th>
                                    <img src="<?= e(asset($finish['sphere'])) ?>" alt="" width="22" height="22" loading="lazy" decoding="async">
                                    <?= e($finish['label']) ?>
                                </th>
                                <?php foreach ($meta['rates'] as $r): ?>
                                    <td><span class="fs-rate fs-rate-<?= e($r) ?>"><?= e($rateWord($r)) ?></span></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
