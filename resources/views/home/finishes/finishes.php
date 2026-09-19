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
                <button type="button" class="finish-view-btn" id="finish-view-btn"
                        aria-label="<?= e(trans('home.finish_zoom')) ?>"
                        title="<?= e(trans('home.finish_zoom')) ?>">
                    <img src="<?= e(asset('images/finishes/swatch-rose-gold.jpg')) ?>"
                         alt="LUFLY Brushed Rose Gold finish"
                         id="finish-showcase-img"
                         class="finish-showcase-img">
                    <span class="finish-zoom-chip" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" width="13" height="13"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/><path d="M11 8v6M8 11h6"/></svg>
                        <?= e(trans('home.finish_zoom')) ?>
                    </span>
                </button>
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

    <!-- full-screen finish viewer -->
    <div class="finish-lightbox" id="finish-lightbox" role="dialog" aria-modal="true"
         aria-label="<?= e(trans('home.finish_zoom')) ?>" hidden>
        <button type="button" class="finish-lightbox-close" id="finish-lightbox-close" aria-label="<?= e(trans('home.finish_close')) ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" width="22" height="22"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </button>
        <button type="button" class="finish-lightbox-nav is-prev" id="finish-lightbox-prev" aria-label="<?= e(trans('home.finish_prev')) ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" width="26" height="26"><path d="m15 18-6-6 6-6"/></svg>
        </button>
        <figure class="finish-lightbox-figure">
            <img src="<?= e(asset('images/finishes/swatch-rose-gold.jpg')) ?>" alt="" id="finish-lightbox-img">
            <figcaption>
                <strong id="finish-lightbox-title"><?= e(trans('home.finish_default_title')) ?></strong>
                <span id="finish-lightbox-tag">PVD TITANIUM VAPOR DEPOSITION</span>
            </figcaption>
        </figure>
        <button type="button" class="finish-lightbox-nav is-next" id="finish-lightbox-next" aria-label="<?= e(trans('home.finish_next')) ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" width="26" height="26"><path d="m9 18 6-6-6-6"/></svg>
        </button>
    </div>
</section>
