<?php
/** @var Core\View\View $view */
$view->pushStyle('frontend/home/hero-slider/hero-slider.css');
$view->pushScript('frontend/home/hero-slider/hero-slider.js');
?>
<section class="deante-hero-container">
    <div class="deante-hero-viewport" id="hero-slider-viewport">
        <div class="deante-hero-track" id="hero-slider-track">
            <!-- Slide 1: smart WC -->
            <div class="deante-hero-slide" title="LUFLY Silia Smart WC Architecture">
                <img src="<?= e(asset('images/lifestyle/hero-slide-smart-wc.jpg')) ?>" 
                     alt="LUFLY Silia Smart WC - Pure Wellness" 
                     class="slide-bg">
                <div class="deante-hero-overlay-luxury">
                    <div class="deante-hero-eyebrow">
                        <span class="deante-hero-eyebrow-dot"></span>
                        <span><?= e(trans('home.hero1_eyebrow')) ?></span>
                    </div>
                    <div class="deante-hero-heading-luxury">
                        <span class="deante-hero-script-luxury">Silia</span>
                        <span class="deante-hero-caps-luxury">WC SMART</span>
                    </div>
                    <p class="deante-hero-desc-luxury"><?= e(trans('home.hero1_desc')) ?></p>
                    <div class="deante-hero-actions-luxury">
                        <a href="<?= e(route('products.index', ['category_id' => 1])) ?>" class="deante-hero-cta-primary">
                            <span><?= e(trans('home.hero1_primary')) ?> &rarr;</span>
                        </a>
                        <a href="https://wa.me/908503040817?text=<?= rawurlencode('Hello LUFLY, I would like to inquire about the Silia Smart WC specification and project pricing.') ?>" 
                           target="_blank" rel="noopener" class="deante-hero-cta-secondary">
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
                <div class="deante-hero-overlay-luxury">
                    <div class="deante-hero-eyebrow">
                        <span class="deante-hero-eyebrow-dot"></span>
                        <span><?= e(trans('home.hero2_eyebrow')) ?></span>
                    </div>
                    <div class="deante-hero-heading-luxury">
                        <span class="deante-hero-script-luxury">Duero</span>
                        <span class="deante-hero-caps-luxury">PVD TAPWARE</span>
                    </div>
                    <p class="deante-hero-desc-luxury"><?= e(trans('home.hero2_desc')) ?></p>
                    <div class="deante-hero-actions-luxury">
                        <a href="<?= e(route('products.index', ['category_id' => 4])) ?>" class="deante-hero-cta-primary">
                            <span><?= e(trans('home.hero2_primary')) ?> &rarr;</span>
                        </a>
                        <a href="#finishes" class="deante-hero-cta-secondary">
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
                <div class="deante-hero-overlay-luxury">
                    <div class="deante-hero-eyebrow">
                        <span class="deante-hero-eyebrow-dot"></span>
                        <span><?= e(trans('home.hero3_eyebrow')) ?></span>
                    </div>
                    <div class="deante-hero-heading-luxury">
                        <span class="deante-hero-script-luxury">Calacatta</span>
                        <span class="deante-hero-caps-luxury">MONOLITH SUITE</span>
                    </div>
                    <p class="deante-hero-desc-luxury"><?= e(trans('home.hero3_desc')) ?></p>
                    <div class="deante-hero-actions-luxury">
                        <a href="<?= e(route('products.index')) ?>" class="deante-hero-cta-primary">
                            <span><?= e(trans('home.hero3_primary')) ?> &rarr;</span>
                        </a>
                        <a href="https://wa.me/908503040817?text=<?= rawurlencode('Hello LUFLY, please share 3D CAD/BIM models for freestanding tubs.') ?>" 
                           target="_blank" rel="noopener" class="deante-hero-cta-secondary">
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
                <div class="deante-hero-overlay-luxury">
                    <div class="deante-hero-eyebrow">
                        <span class="deante-hero-eyebrow-dot"></span>
                        <span><?= e(trans('home.hero4_eyebrow')) ?></span>
                    </div>
                    <div class="deante-hero-heading-luxury">
                        <span class="deante-hero-script-luxury">Kallisto</span>
                        <span class="deante-hero-caps-luxury">RAIN SHOWER</span>
                    </div>
                    <p class="deante-hero-desc-luxury"><?= e(trans('home.hero4_desc')) ?></p>
                    <div class="deante-hero-actions-luxury">
                        <a href="<?= e(route('products.index')) ?>" class="deante-hero-cta-primary">
                            <span><?= e(trans('home.hero4_primary')) ?> &rarr;</span>
                        </a>
                        <a href="#rituals" class="deante-hero-cta-secondary">
                            <span><?= e(trans('home.hero4_secondary')) ?></span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pagination dots -->
        <div class="deante-hero-dots" id="hero-slider-dots">
            <span class="deante-hero-dot is-active" data-slide="0" aria-label="Slide 1"></span>
            <span class="deante-hero-dot" data-slide="1" aria-label="Slide 2"></span>
            <span class="deante-hero-dot" data-slide="2" aria-label="Slide 3"></span>
            <span class="deante-hero-dot" data-slide="3" aria-label="Slide 4"></span>
        </div>
    </div>

    <!-- Arrow buttons -->
    <div class="deante-hero-controls">
        <button type="button" class="deante-hero-arrow" id="hero-prev-btn" aria-label="Previous Slide">&lt;</button>
        <button type="button" class="deante-hero-arrow" id="hero-next-btn" aria-label="Next Slide">&gt;</button>
    </div>
</section>
