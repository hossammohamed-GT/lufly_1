<?php
/**
 * Home component: the finishes lab.
 *
 * A two-column finish configurator, the way premium sanitaryware makers
 * (Dornbracht, Gessi, AXOR) present their surface range: the left column
 * carries the headline, the finish cards and the CTAs; the right column is
 * the product stage whose image, tint, specs and counter all follow the
 * selected finish. Every text that changes per finish is a translation key;
 * the per-finish metadata rides on the cards as data attributes so the
 * script never hardcodes copy.
 *
 * Finish list confirmed with the factory (management review): every finish
 * below is currently available, "Brushed Steel" is renamed to "Brushed
 * Nickel", Mirror Gold plus the Gunmetal variants (Brushed Gunmetal / Gun
 * Gray) were added, and the PVD badge is shown only where PVD is used.
 */

$view->pushDeferredStyle('frontend/home/finishes/finishes.css');
$view->pushScript('frontend/home/finishes/finishes.js');

/* Per-finish presentation data. phrase/story are translation keys; scratch
   is the factory scratch-resistance figure; rates drive the comparison
   drawer (durability / fingerprints / luxury); tint is the ambient wash the
   stage adopts while that finish is selected. */
$finishMeta = [
    'brushed-rose-gold'  => ['phrase' => 'finishes_phrase_brushed_rose_gold',  'story' => 'finishes_story_brushed_rose_gold',  'scratch' => 92, 'rates' => ['high', 'low', 'high'],   'tint' => 'rgba(214, 158, 128, 0.30)', 'accent' => '#A06538'],
    'chrome'             => ['phrase' => 'finishes_phrase_chrome',             'story' => 'finishes_story_chrome',             'scratch' => 78, 'rates' => ['medium', 'high', 'medium'], 'tint' => 'rgba(196, 208, 214, 0.30)', 'accent' => '#62676D'],
    'brushed-gold'       => ['phrase' => 'finishes_phrase_brushed_gold',       'story' => 'finishes_story_brushed_gold',       'scratch' => 94, 'rates' => ['high', 'low', 'high'],   'tint' => 'rgba(226, 190, 116, 0.30)', 'accent' => '#9A742A'],
    'mirror-gold'        => ['phrase' => 'finishes_phrase_mirror_gold',        'story' => 'finishes_story_mirror_gold',        'scratch' => 90, 'rates' => ['medium', 'high', 'high'],  'tint' => 'rgba(236, 204, 132, 0.32)', 'accent' => '#8F6E1F'],
    'matte-black'        => ['phrase' => 'finishes_phrase_matte_black',        'story' => 'finishes_story_matte_black',        'scratch' => 85, 'rates' => ['high', 'medium', 'high'], 'tint' => 'rgba(70, 70, 74, 0.30)', 'accent' => '#26262B'],
    'brushed-nickel'     => ['phrase' => 'finishes_phrase_brushed_nickel',     'story' => 'finishes_story_brushed_nickel',     'scratch' => 93, 'rates' => ['high', 'low', 'medium'],  'tint' => 'rgba(176, 178, 180, 0.28)', 'accent' => '#6E7176'],
    'gunmetal'           => ['phrase' => 'finishes_phrase_gunmetal',           'story' => 'finishes_story_gunmetal',           'scratch' => 94, 'rates' => ['high', 'medium', 'high'], 'tint' => 'rgba(96, 116, 132, 0.30)', 'accent' => '#4C5763'],
    'brushed-gunmetal'   => ['phrase' => 'finishes_phrase_brushed_gunmetal',   'story' => 'finishes_story_brushed_gunmetal',   'scratch' => 93, 'rates' => ['high', 'low', 'high'],   'tint' => 'rgba(84, 100, 114, 0.28)', 'accent' => '#525F6B'],
    'gun-gray'           => ['phrase' => 'finishes_phrase_gun_gray',           'story' => 'finishes_story_gun_gray',           'scratch' => 92, 'rates' => ['high', 'medium', 'medium'], 'tint' => 'rgba(140, 146, 152, 0.26)', 'accent' => '#667079'],
];

$finishes = [
    ['key' => 'brushed-rose-gold', 'img' => 'images/finishes/swatch-rose-gold.jpg',      'label' => 'Rose Gold',      'tech' => 'PVD'],
    ['key' => 'chrome',            'img' => 'images/finishes/swatch-chrome.jpg',          'label' => 'Chrome',         'tech' => 'Electroplated'],
    ['key' => 'brushed-gold',      'img' => 'images/finishes/swatch-gold.jpg',            'label' => 'Brushed Gold',   'tech' => 'PVD'],
    ['key' => 'mirror-gold',       'img' => 'images/finishes/swatch-mirror-gold.jpg',     'label' => 'Mirror Gold',    'tech' => 'PVD'],
    ['key' => 'matte-black',       'img' => 'images/finishes/swatch-black.jpg',           'label' => 'Matte Black',    'tech' => 'Powder Coat'],
    ['key' => 'brushed-nickel',    'img' => 'images/finishes/swatch-brushed-nickel.jpg',  'label' => 'Brushed Nickel', 'tech' => 'PVD'],
    ['key' => 'gunmetal',          'img' => 'images/finishes/swatch-gunmetal.jpg',        'label' => 'Gunmetal',       'tech' => 'PVD'],
    ['key' => 'brushed-gunmetal',  'img' => 'images/finishes/swatch-brushed-gunmetal.jpg','label' => 'Brushed Gunmetal', 'tech' => 'PVD'],
    ['key' => 'gun-gray',          'img' => 'images/finishes/swatch-gun-gray.jpg',        'label' => 'Gun Gray',       'tech' => 'PVD'],
];

/* The "View in Spaces" scenes reuse the hero lifestyle artwork - each place
   gets the scene that actually shows that kind of room. */
$spaces = [
    ['key' => 'bathroom', 'img' => 'images/lifestyle/heroc-1-light.jpg'],
    ['key' => 'kitchen',  'img' => 'images/lifestyle/heroc-4-light.jpg'],
    ['key' => 'hotel',    'img' => 'images/lifestyle/heroc-5-light.jpg'],
    ['key' => 'villa',    'img' => 'images/lifestyle/heroc-2-light.jpg'],
    ['key' => 'spa',      'img' => 'images/lifestyle/heroc-3-light.jpg'],
];

$first = $finishes[0]['key'];
$firstMeta = $finishMeta[$first];
/* the phrase's colour rides the section as a CSS variable (set per finish
   by finishes.js); dark theme lightens it via color-mix in the CSS */
$rateWord = static fn (string $r): string => trans('home.finishes_rating_' . $r);
?>
<section class="band finishes-section scroll-section" id="finishes" data-finish="<?= e($first) ?>" style="--fs-accent: <?= e($firstMeta['accent']) ?>">
    <div class="finishes-backdrop" aria-hidden="true"></div>
    <div class="fs-tint fs-tint-a" aria-hidden="true"></div>
    <div class="fs-tint fs-tint-b" aria-hidden="true"></div>

    <div class="container finishes-container">
        <div class="fs-grid">

            <!-- ============ LEFT: headline, story, cards, CTAs ============ -->
            <div class="fs-left">
                <span class="section-tag fs-anim" style="--d: 0"><?= e(trans('home.finishes_kicker')) ?></span>

                <h2 class="fs-headline fs-anim" style="--d: 1">
                    <?= e(trans('home.finishes_headline_lead')) ?><br>
                    <em class="fs-phrase" id="fs-phrase"><?= e(trans('home.' . $firstMeta['phrase'])) ?></em>
                </h2>

                <p class="fs-desc fs-anim" id="finish-desc" style="--d: 2"><?= e(trans('home.finish_default_desc')) ?></p>

                <div class="fs-cards fs-anim" style="--d: 3" role="group" aria-label="<?= e(trans('home.finishes_kicker')) ?>">
                    <?php foreach ($finishes as $i => $finish):
                        $meta = $finishMeta[$finish['key']] ?? null;
                        if ($meta === null) { continue; }
                        $chip = str_replace('/swatch-', '/chip-', $finish['img']);
                    ?>
                        <button type="button"
                                class="fs-card<?= $i === 0 ? ' is-active' : '' ?>"
                                data-finish="<?= e($finish['key']) ?>"
                                data-phrase="<?= e(trans('home.' . $meta['phrase'])) ?>"
                                data-story="<?= e(trans('home.' . $meta['story'])) ?>"
                                data-tint="<?= e($meta['tint']) ?>"
                                data-accent="<?= e($meta['accent']) ?>"
                                aria-pressed="<?= $i === 0 ? 'true' : 'false' ?>"
                                style="--i: <?= (int) $i ?>">
                            <span class="fs-card-chip">
                                <img src="<?= e(asset($chip)) ?>" alt="" width="44" height="44" loading="lazy" decoding="async">
                            </span>
                            <span class="fs-card-text">
                                <b class="fs-card-name"><?= e($finish['label']) ?></b>
                                <small class="fs-card-tech"><?= e($finish['tech']) ?> · <?= $meta['scratch'] ?>%</small>
                            </span>
                            <span class="fs-card-view" aria-hidden="true"><?= e(trans('home.finishes_view_finish')) ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>

                <div class="fs-ctas fs-anim" style="--d: 4">
                    <a href="<?= e(route('products.index')) ?>" class="btn-primary-teal fs-cta-main">
                        <span><?= e(trans('home.finishes_explore')) ?></span>
                    </a>
                    <button type="button" class="btn-secondary-glass fs-compare-open" data-fs-compare>
                        <span><?= e(trans('home.finishes_compare')) ?></span>
                    </button>
                </div>
            </div>

            <!-- ============ RIGHT: the product stage ============ -->
            <div class="fs-right fs-anim" style="--d: 2">
                <figure class="fs-stage">
                    <div class="fs-stage-imgs">
                        <?= $view->component('responsive-image', [
                            'src'    => $finishes[0]['img'],
                            'alt'    => 'LUFLY ' . $finishes[0]['label'] . ' finish',
                            'class'  => 'fs-img fs-img-a is-on',
                            'width'  => 1376,
                            'height' => 768,
                            'sizes'  => '(max-width: 980px) 92vw, 56vw',
                        ]) ?>
                        <?= $view->component('responsive-image', [
                            'src'    => $finishes[0]['img'],
                            'alt'    => '',
                            'class'  => 'fs-img fs-img-b',
                            'width'  => 1376,
                            'height' => 768,
                            'sizes'  => '(max-width: 980px) 92vw, 56vw',
                        ]) ?>
                    </div>

                    <figcaption class="fs-stage-caption">
                        <span id="fs-story"><?= e(trans('home.' . $firstMeta['story'])) ?></span>
                    </figcaption>

                    <!-- interactive hotspots: the third rail of the story -->
                    <button type="button" class="fs-hotspot" data-spot="pvd" style="--x: 52%; --y: 24%">
                        <span class="fs-spot-dot" aria-hidden="true"></span>
                        <span class="fs-spot-tip">
                            <b><?= e(trans('home.finishes_hs_pvd_t')) ?></b>
                            <i><?= e(trans('home.finishes_hs_pvd_d')) ?></i>
                        </span>
                    </button>
                    <button type="button" class="fs-hotspot" data-spot="core" style="--x: 36%; --y: 58%">
                        <span class="fs-spot-dot" aria-hidden="true"></span>
                        <span class="fs-spot-tip">
                            <b><?= e(trans('home.finishes_hs_core_t')) ?></b>
                            <i><?= e(trans('home.finishes_hs_core_d')) ?></i>
                        </span>
                    </button>
                    <button type="button" class="fs-hotspot" data-spot="aerator" style="--x: 64%; --y: 74%">
                        <span class="fs-spot-dot" aria-hidden="true"></span>
                        <span class="fs-spot-tip">
                            <b><?= e(trans('home.finishes_hs_aerator_t')) ?></b>
                            <i><?= e(trans('home.finishes_hs_aerator_d')) ?></i>
                        </span>
                    </button>

                    <figcaption class="fs-stage-meta">
                        <span class="finish-spec-pill" id="finish-tag">PVD TITANIUM VAPOR DEPOSITION</span>
                        <span class="finish-counter"><b id="finish-index">01</b> / <?= e(str_pad((string) count($finishes), 2, '0', STR_PAD_LEFT)) ?></span>
                    </figcaption>

                    <button type="button" class="fs-spaces-open" data-fs-spaces>
                        <span><?= e(trans('home.finishes_view_space')) ?></span>
                    </button>
                </figure>

                <div class="fs-details">
                    <h3 class="finish-title" id="finish-title"><?= e(trans('home.finish_default_title')) ?></h3>

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
                            $chip = str_replace('/swatch-', '/chip-', $finish['img']);
                        ?>
                            <tr data-finish-row="<?= e($finish['key']) ?>">
                                <th>
                                    <img src="<?= e(asset($chip)) ?>" alt="" width="22" height="22" loading="lazy" decoding="async">
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

    <!-- ============ view in spaces modal ============ -->
    <div class="fs-modal" data-fs-modal hidden>
        <div class="fs-modal-panel" role="dialog" aria-modal="true" aria-label="<?= e(trans('home.finishes_view_space')) ?>">
            <div class="fs-modal-head">
                <b><?= e(trans('home.finishes_view_space')) ?></b>
                <button type="button" class="fs-modal-close" data-fs-spaces-close aria-label="<?= e(trans('home.finishes_compare_close')) ?>">×</button>
            </div>
            <div class="fs-modal-places">
                <?php foreach ($spaces as $i => $space): ?>
                    <button type="button"
                            class="fs-place<?= $i === 0 ? ' is-active' : '' ?>"
                            data-place="<?= e($space['key']) ?>"
                            data-place-img="<?= e(asset($space['img'])) ?>"
                            style="--i: <?= (int) $i ?>">
                        <?= e(trans('home.finishes_space_' . $space['key'])) ?>
                    </button>
                <?php endforeach; ?>
            </div>
            <div class="fs-modal-stage">
                <img src="<?= e(asset($spaces[0]['img'])) ?>" alt="<?= e(trans('home.finishes_space_bathroom')) ?>" id="fs-modal-img" width="1376" height="768" loading="lazy" decoding="async">
            </div>
        </div>
    </div>
</section>
