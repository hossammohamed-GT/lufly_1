<?php
/** @var Core\View\View $view */
$view->pushStyle('frontend/home/finishes/finishes.css');
$view->pushScript('frontend/home/finishes/finishes.js');

$finishes = [
    [
        'key' => 'brushed-rose-gold',
        'img' => 'images/finishes/swatch-rose-gold.jpg',
        'label' => 'Rose Gold',
        'title' => trans('home.finish_rose_title'),
        'desc' => trans('home.finish_rose_desc'),
    ],
    [
        'key' => 'chrome',
        'img' => 'images/finishes/swatch-chrome.jpg',
        'label' => 'Chrome',
        'title' => trans('home.finish_chrome_title'),
        'desc' => trans('home.finish_chrome_desc'),
    ],
    [
        'key' => 'brushed-gold',
        'img' => 'images/finishes/swatch-gold.jpg',
        'label' => 'Brushed Gold',
        'title' => trans('home.finish_gold_title'),
        'desc' => trans('home.finish_gold_desc'),
    ],
    [
        'key' => 'matte-black',
        'img' => 'images/finishes/swatch-black.jpg',
        'label' => 'Matte Black',
        'title' => trans('home.finish_black_title'),
        'desc' => trans('home.finish_black_desc'),
    ],
    [
        'key' => 'brushed-steel',
        'img' => 'images/finishes/swatch-steel.jpg',
        'label' => 'Brushed Steel',
        'title' => trans('home.finish_steel_title'),
        'desc' => trans('home.finish_steel_desc'),
    ],
    [
        'key' => 'gunmetal',
        'img' => 'images/finishes/swatch-gunmetal.jpg',
        'label' => 'Gunmetal',
        'title' => trans('home.finish_gunmetal_title'),
        'desc' => trans('home.finish_gunmetal_desc'),
    ],
];
?>
<section class="band finishes-section scroll-section" id="finishes">
    <div class="finishes-backdrop" aria-hidden="true"></div>
    <div class="container finishes-container">
        <div class="finishes-heading-row scroll-reveal">
            <div class="section-header-left">
                <span class="section-tag"><?= e(trans('home.finishes_tag')) ?></span>
                <h2 class="section-title"><?= e(trans('home.finishes_title_before')) ?><br><em><?= e(trans('home.finishes_title_emphasis')) ?></em> <?= e(trans('home.finishes_title_after')) ?></h2>
                <p class="section-subtitle"><?= e(trans('home.finishes_desc')) ?></p>
            </div>
            <div class="finishes-heading-note"><?= e(trans('home.finishes_note_1')) ?><br><?= e(trans('home.finishes_note_2')) ?><br><?= e(trans('home.finishes_note_3')) ?><br><?= e(trans('home.finishes_note_4')) ?><span></span></div>
        </div>

        <div class="finishes-swatch-grid scroll-reveal" data-delay="1">
            <?php foreach ($finishes as $i => $finish): ?>
                <button type="button"
                        class="finish-swatch-btn <?= $i === 0 ? 'is-active' : '' ?>"
                        data-finish="<?= e($finish['key']) ?>"
                        data-index="<?= (int) $i ?>"
                        data-title="<?= e($finish['title']) ?>"
                        data-desc="<?= e($finish['desc']) ?>"
                        aria-pressed="<?= $i === 0 ? 'true' : 'false' ?>">
                    <img src="<?= e(asset($finish['img'])) ?>" alt="<?= e($finish['label']) ?> finish" loading="lazy" decoding="async">
                    <span class="swatch-label"><?= e($finish['label']) ?></span>
                    <small>PVD</small>
                </button>
            <?php endforeach; ?>
        </div>

        <div class="finish-showcase-box scroll-reveal" data-delay="2">
            <figure class="finish-showcase-media">
                <img src="<?= e(asset('images/finishes/swatch-rose-gold.jpg')) ?>"
                     alt="LUFLY Brushed Rose Gold finish"
                     id="finish-showcase-img"
                     class="finish-showcase-img">
                <figcaption class="finish-image-caption"><?= e(trans('home.finish_caption')) ?></figcaption>
            </figure>

            <div class="finish-showcase-details">
                <div class="finish-detail-meta">
                    <span class="finish-spec-pill" id="finish-tag">PVD TITANIUM VAPOR DEPOSITION</span>
                    <span class="finish-counter"><b id="finish-index">01</b> / <?= e(str_pad((string) count($finishes), 2, '0', STR_PAD_LEFT)) ?></span>
                </div>
                <h3 class="finish-title" id="finish-title"><?= e(trans('home.finish_default_title')) ?></h3>
                <p class="finish-desc" id="finish-desc"><?= e(trans('home.finish_default_desc')) ?></p>

                <div class="finish-tech-specs">
                    <div class="tech-spec-item">
                        <span id="finish-base-label"><?= e(trans('home.finish_base_label')) ?></span>
                        <b id="finish-base">Solid Brass CW617N</b>
                    </div>
                    <div class="tech-spec-item">
                        <span id="finish-coating-label"><?= e(trans('home.finish_coating_label')) ?></span>
                        <b id="finish-coating">PVD Titanium 0.4µm</b>
                    </div>
                    <div class="tech-spec-item">
                        <span id="finish-cartridge-label"><?= e(trans('home.finish_cartridge_label')) ?></span>
                        <b id="finish-cartridge">Kerox® Hungary 35mm Ceramic</b>
                    </div>
                    <div class="tech-spec-item">
                        <span id="finish-aerator-label"><?= e(trans('home.finish_aerator_label')) ?></span>
                        <b id="finish-aerator">Neoperl® Coin-Slot Pro-Eco 5.7 L/min</b>
                    </div>
                </div>

                <div class="finish-actions">
                    <a href="<?= e(route('contact')) ?>" id="finish-wa-btn" class="btn-primary-teal">
                        <span><?= e(trans('home.finish_inquire')) ?></span>
                    </a>
                    <a href="<?= e(route('products.index', ['category' => 'architectural-ceramics'])) ?>" class="btn-secondary-glass">
                        <span><?= e(trans('home.view_mixers')) ?></span>
                    </a>
                </div>
            </div>
        </div>
    </div>

</section>
