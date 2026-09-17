<?php
/** @var Core\View\View $view */
$view->pushStyle('frontend/home/finishes/finishes.css');
$view->pushScript('frontend/home/finishes/finishes.js');
?>
<section class="band finishes-section" id="finishes">
    <div class="finishes-backdrop" aria-hidden="true"></div>
    <div class="container finishes-container">
        <div class="finishes-heading-row">
            <div class="section-header-left">
                <span class="section-tag"><?= e(trans('home.finishes_tag')) ?></span>
                <h2 class="section-title"><?= e(trans('home.finishes_title_before')) ?><br><em><?= e(trans('home.finishes_title_emphasis')) ?></em> <?= e(trans('home.finishes_title_after')) ?></h2>
                <p class="section-subtitle"><?= e(trans('home.finishes_desc')) ?></p>
            </div>
            <div class="finishes-heading-note"><?= e(trans('home.finishes_note_1')) ?><br><?= e(trans('home.finishes_note_2')) ?><br><?= e(trans('home.finishes_note_3')) ?><br><?= e(trans('home.finishes_note_4')) ?><span></span></div>
        </div>

        <div class="finishes-swatch-row">
            <button type="button" class="finish-swatch-btn is-active" data-finish="brushed-rose-gold" data-title="<?= e(trans('home.finish_rose_title')) ?>" data-desc="<?= e(trans('home.finish_rose_desc')) ?>" aria-label="Brushed Rose Gold">
                <img src="<?= e(asset('images/finishes/brushed-rose-gold.jpg')) ?>" alt="Rose Gold finish"><span class="swatch-label">Rose Gold</span><small>PVD</small>
            </button>
            <button type="button" class="finish-swatch-btn" data-finish="chrome" data-title="<?= e(trans('home.finish_chrome_title')) ?>" data-desc="<?= e(trans('home.finish_chrome_desc')) ?>" aria-label="Mirror Chrome">
                <img src="<?= e(asset('images/finishes/chrome.jpg')) ?>" alt="Chrome finish"><span class="swatch-label">Chrome</span><small>PVD</small>
            </button>
            <button type="button" class="finish-swatch-btn" data-finish="brushed-gold" data-title="<?= e(trans('home.finish_gold_title')) ?>" data-desc="<?= e(trans('home.finish_gold_desc')) ?>" aria-label="Brushed Gold">
                <img src="<?= e(asset('images/finishes/brushed-gold.jpg')) ?>" alt="Brushed Gold finish"><span class="swatch-label">Brushed Gold</span><small>PVD</small>
            </button>
            <button type="button" class="finish-swatch-btn" data-finish="matte-black" data-title="<?= e(trans('home.finish_black_title')) ?>" data-desc="<?= e(trans('home.finish_black_desc')) ?>" aria-label="Matte Black">
                <img src="<?= e(asset('images/finishes/matte-black.jpg')) ?>" alt="Matte Black finish"><span class="swatch-label">Matte Black</span><small>PVD</small>
            </button>
            <button type="button" class="finish-swatch-btn" data-finish="brushed-steel" data-title="<?= e(trans('home.finish_steel_title')) ?>" data-desc="<?= e(trans('home.finish_steel_desc')) ?>" aria-label="Brushed Steel">
                <img src="<?= e(asset('images/finishes/brushed-steel.jpg')) ?>" alt="Brushed Steel finish"><span class="swatch-label">Brushed Steel</span><small>PVD</small>
            </button>
            <button type="button" class="finish-swatch-btn" data-finish="gunmetal" data-title="<?= e(trans('home.finish_gunmetal_title')) ?>" data-desc="<?= e(trans('home.finish_gunmetal_desc')) ?>" aria-label="Gunmetal Grey">
                <img src="<?= e(asset('images/finishes/gunmetal.jpg')) ?>" alt="Gunmetal finish"><span class="swatch-label">Gunmetal</span><small>PVD</small>
            </button>
        </div>

        <div class="finish-showcase-box">
            <div class="finish-showcase-image-wrap">
                <img src="<?= e(asset('images/finishes/brushed-rose-gold.jpg')) ?>" 
                     alt="LUFLY Brushed Rose Gold finish"
                     id="finish-showcase-img" 
                     class="finish-showcase-img">
                <span class="finish-image-caption">A finish<br>for a higher<br>standard</span>
            </div>

            <div class="finish-showcase-details">
                <div class="finish-detail-meta"><span class="finish-spec-pill" id="finish-tag">PVD TITANIUM VAPOR DEPOSITION</span><span>01 / 06</span></div>
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
                    <a href="https://wa.me/908503040817?text=<?= rawurlencode('Hello LUFLY, I am specifying the Brushed Rose Gold finish for an architectural project. Please share high-res renders and export pricing.') ?>" 
                       id="finish-wa-btn" 
                       target="_blank" 
                       rel="noopener" 
                       class="btn-primary-teal">
                        <span><?= e(trans('home.finish_inquire')) ?></span>
                    </a>
                    <a href="<?= e(route('products.index', ['category_id' => 4])) ?>" class="btn-secondary-glass">
                        <span><?= e(trans('home.view_mixers')) ?></span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
