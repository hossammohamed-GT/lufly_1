<?php
/**
 * @var Core\View\View $view
 *
 * v3 "Open Monolith" — the floating glass card is gone. Copy now sits
 * directly on the photo behind a scrim, fully responsive (stacks + centers
 * on mobile). Same trans() keys as before, same 5 new ticker keys
 * (home.ticker_1..5) — see hero-slider-lang-additions.php.
 */
$view->pushStyle('frontend/home/hero-slider/hero-slider.css');
$view->pushScript('frontend/home/hero-slider/hero-slider.js');
?>
<section class="deante-hero-container">
    <div class="deante-hero-viewport" id="hero-slider-viewport">

        <!-- Ambient light blooms — pure CSS, GPU-only motion -->
        <div class="hero-ambient-blob hero-ambient-blob--a" aria-hidden="true"></div>
        <div class="hero-ambient-blob hero-ambient-blob--b" aria-hidden="true"></div>

        <div class="deante-hero-track" id="hero-slider-track">
            <!-- Slide 1: smart WC -->
            <div class="deante-hero-slide" title="LUFLY Silia Smart WC Architecture">
                <img src="<?= e(asset('images/lifestyle/hero-slide-smart-wc.jpg')) ?>"
                     alt="LUFLY Silia Smart WC - Pure Wellness"
                     class="slide-bg">
                <div class="hero-scrim" aria-hidden="true"></div>
                <div class="deante-hero-content">
                    <div class="deante-hero-eyebrow">
                        <span class="deante-hero-eyebrow-dot"></span>
                        <span><?= e(trans('home.hero1_eyebrow')) ?></span>
                    </div>
                    <h2 class="deante-hero-heading-luxury">
                        <span class="hero-line-mask"><span class="deante-hero-script-luxury">Silia</span></span>
                        <span class="hero-line-mask"><span class="deante-hero-caps-luxury">WC SMART</span></span>
                    </h2>
                    <p class="deante-hero-desc-luxury"><?= e(trans('home.hero1_desc')) ?></p>
                    <div class="deante-hero-actions-luxury">
                        <a href="<?= e(route('products.index', ['category_id' => 1])) ?>" class="deante-hero-cta-primary">
                            <span><?= e(trans('home.hero1_primary')) ?></span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                        <a href="https://wa.me/908503040817?text=<?= rawurlencode('Hello LUFLY, I would like to inquire about the Silia Smart WC specification and project pricing.') ?>"
                           target="_blank" rel="noopener" class="deante-hero-cta-secondary">
                            <i class="fa-brands fa-whatsapp"></i>
                            <span><?= e(trans('home.whatsapp_specifier')) ?></span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Slide 2: faucets -->
            <div class="deante-hero-slide" title="LUFLY Architectural Faucets & Mixers">
                <img src="<?= e(asset('images/lifestyle/hero-slide-basin-mixer.jpg')) ?>"
                     alt="LUFLY Architectural Basin Mixers"
                     class="slide-bg">
                <div class="hero-scrim" aria-hidden="true"></div>
                <div class="deante-hero-content">
                    <div class="deante-hero-eyebrow">
                        <span class="deante-hero-eyebrow-dot"></span>
                        <span><?= e(trans('home.hero2_eyebrow')) ?></span>
                    </div>
                    <h2 class="deante-hero-heading-luxury">
                        <span class="hero-line-mask"><span class="deante-hero-script-luxury">Duero</span></span>
                        <span class="hero-line-mask"><span class="deante-hero-caps-luxury">PVD TAPWARE</span></span>
                    </h2>
                    <p class="deante-hero-desc-luxury"><?= e(trans('home.hero2_desc')) ?></p>
                    <div class="deante-hero-actions-luxury">
                        <a href="<?= e(route('products.index', ['category_id' => 4])) ?>" class="deante-hero-cta-primary">
                            <span><?= e(trans('home.hero2_primary')) ?></span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                        <a href="#finishes" class="deante-hero-cta-secondary">
                            <i class="fa-solid fa-palette"></i>
                            <span><?= e(trans('home.hero2_secondary')) ?></span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Slide 3: spa suite -->
            <div class="deante-hero-slide" title="LUFLY Architectural Sanctuaries">
                <img src="<?= e(asset('images/lifestyle/hero-slide-freestanding-tub.jpg')) ?>"
                     alt="LUFLY Luxury Calacatta Suite"
                     class="slide-bg">
                <div class="hero-scrim" aria-hidden="true"></div>
                <div class="deante-hero-content">
                    <div class="deante-hero-eyebrow">
                        <span class="deante-hero-eyebrow-dot"></span>
                        <span><?= e(trans('home.hero3_eyebrow')) ?></span>
                    </div>
                    <h2 class="deante-hero-heading-luxury">
                        <span class="hero-line-mask"><span class="deante-hero-script-luxury">Calacatta</span></span>
                        <span class="hero-line-mask"><span class="deante-hero-caps-luxury">MONOLITH SUITE</span></span>
                    </h2>
                    <p class="deante-hero-desc-luxury"><?= e(trans('home.hero3_desc')) ?></p>
                    <div class="deante-hero-actions-luxury">
                        <a href="<?= e(route('products.index')) ?>" class="deante-hero-cta-primary">
                            <span><?= e(trans('home.hero3_primary')) ?></span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                        <a href="https://wa.me/908503040817?text=<?= rawurlencode('Hello LUFLY, please share 3D CAD/BIM models for freestanding tubs.') ?>"
                           target="_blank" rel="noopener" class="deante-hero-cta-secondary">
                            <i class="fa-solid fa-cube"></i>
                            <span><?= e(trans('home.hero3_secondary')) ?></span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Slide 4: rain shower -->
            <div class="deante-hero-slide" title="LUFLY Concealed Thermostatic Shower Systems">
                <img src="<?= e(asset('images/lifestyle/hero-slide-rain-shower.jpg')) ?>"
                     alt="LUFLY Kallisto Concealed Thermostatic Shower"
                     class="slide-bg">
                <div class="hero-scrim" aria-hidden="true"></div>
                <div class="deante-hero-content">
                    <div class="deante-hero-eyebrow">
                        <span class="deante-hero-eyebrow-dot"></span>
                        <span><?= e(trans('home.hero4_eyebrow')) ?></span>
                    </div>
                    <h2 class="deante-hero-heading-luxury">
                        <span class="hero-line-mask"><span class="deante-hero-script-luxury">Kallisto</span></span>
                        <span class="hero-line-mask"><span class="deante-hero-caps-luxury">RAIN SHOWER</span></span>
                    </h2>
                    <p class="deante-hero-desc-luxury"><?= e(trans('home.hero4_desc')) ?></p>
                    <div class="deante-hero-actions-luxury">
                        <a href="<?= e(route('products.index')) ?>" class="deante-hero-cta-primary">
                            <span><?= e(trans('home.hero4_primary')) ?></span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                        <a href="#rituals" class="deante-hero-cta-secondary">
                            <i class="fa-solid fa-droplet"></i>
                            <span><?= e(trans('home.hero4_secondary')) ?></span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pagination dots with fill-progress (syncs to autoplay duration) -->
        <div class="deante-hero-dots" id="hero-slider-dots">
            <span class="deante-hero-dot is-active" data-slide="0" aria-label="Slide 1"><span class="dot-progress"></span></span>
            <span class="deante-hero-dot" data-slide="1" aria-label="Slide 2"><span class="dot-progress"></span></span>
            <span class="deante-hero-dot" data-slide="2" aria-label="Slide 3"><span class="dot-progress"></span></span>
            <span class="deante-hero-dot" data-slide="3" aria-label="Slide 4"><span class="dot-progress"></span></span>
        </div>

        <!-- Arrow buttons -->
        <div class="deante-hero-controls">
            <button type="button" class="deante-hero-arrow" id="hero-prev-btn" aria-label="Previous Slide">
                <i class="fa-solid fa-chevron-left"></i>
            </button>
            <button type="button" class="deante-hero-arrow" id="hero-next-btn" aria-label="Next Slide">
                <i class="fa-solid fa-chevron-right"></i>
            </button>
        </div>
    </div>

    <!-- Continuous stats marquee — the "always something moving" strip -->
    <div class="hero-ticker" aria-hidden="true">
        <div class="hero-ticker-track">
            <?php for ($pass = 0; $pass < 2; $pass++): // duplicated for a seamless loop ?>
                <span class="hero-ticker-item"><i class="fa-solid fa-industry"></i><?= e(trans('home.ticker_1')) ?></span>
                <span class="hero-ticker-sep"></span>
                <span class="hero-ticker-item"><i class="fa-solid fa-earth-americas"></i><?= e(trans('home.ticker_2')) ?></span>
                <span class="hero-ticker-sep"></span>
                <span class="hero-ticker-item"><i class="fa-solid fa-droplet"></i><?= e(trans('home.ticker_3')) ?></span>
                <span class="hero-ticker-sep"></span>
                <span class="hero-ticker-item"><i class="fa-solid fa-shield-halved"></i><?= e(trans('home.ticker_4')) ?></span>
                <span class="hero-ticker-sep"></span>
                <span class="hero-ticker-item"><i class="fa-solid fa-gem"></i><?= e(trans('home.ticker_5')) ?></span>
                <span class="hero-ticker-sep"></span>
            <?php endfor; ?>
        </div>
    </div>
</section>