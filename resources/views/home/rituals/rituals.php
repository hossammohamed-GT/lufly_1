<?php
/** @var Core\View\View $view */
$view->pushStyle('frontend/home/rituals/rituals.css');
?>
<section class="rituals-section scroll-section" id="rituals">
    <div class="container">
        <div class="section-header-center scroll-reveal">
            <span class="section-tag"><?= e(trans('home.rituals_tag')) ?></span>
            <h2 class="section-title"><?= e(trans('home.rituals_title')) ?></h2>
            <p class="section-subtitle"><?= e(trans('home.rituals_desc')) ?></p>
        </div>

        <div class="rituals-grid">
            <div class="ritual-card scroll-reveal" data-delay="1">
                <div class="ritual-figure">
                    <img src="<?= e(asset('images/lifestyle/ritual-flow.jpg')) ?>"
                         alt="<?= e(trans('home.ritual_1_title')) ?>"
                         class="ritual-img" width="520" height="340"
                         loading="lazy" decoding="async">
                </div>
                <div class="ritual-number">01 / <?= e(trans('home.ritual_1_label')) ?></div>
                <h3><?= e(trans('home.ritual_1_title')) ?></h3>
                <p><?= e(trans('home.ritual_1_desc')) ?></p>
                <span class="ritual-metric-tag"><?= e(trans('home.ritual_1_metric')) ?></span>
            </div>

            <div class="ritual-card scroll-reveal" data-delay="2">
                <div class="ritual-figure">
                    <img src="<?= e(asset('images/lifestyle/ritual-cartridge.jpg')) ?>"
                         alt="<?= e(trans('home.ritual_2_title')) ?>"
                         class="ritual-img" width="520" height="340"
                         loading="lazy" decoding="async">
                </div>
                <div class="ritual-number">02 / <?= e(trans('home.ritual_2_label')) ?></div>
                <h3><?= e(trans('home.ritual_2_title')) ?></h3>
                <p><?= e(trans('home.ritual_2_desc')) ?></p>
                <span class="ritual-metric-tag"><?= e(trans('home.ritual_2_metric')) ?></span>
            </div>

            <div class="ritual-card scroll-reveal" data-delay="3">
                <div class="ritual-figure">
                    <img src="<?= e(asset('images/lifestyle/ritual-thermostat.jpg')) ?>"
                         alt="<?= e(trans('home.ritual_3_title')) ?>"
                         class="ritual-img" width="520" height="340"
                         loading="lazy" decoding="async">
                </div>
                <div class="ritual-number">03 / <?= e(trans('home.ritual_3_label')) ?></div>
                <h3><?= e(trans('home.ritual_3_title')) ?></h3>
                <p><?= e(trans('home.ritual_3_desc')) ?></p>
                <span class="ritual-metric-tag"><?= e(trans('home.ritual_3_metric')) ?></span>
            </div>
        </div>
    </div>
</section>
