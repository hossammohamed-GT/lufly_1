<?php
/** @var Core\View\View $view */
$view->pushStyle('frontend/home/trust-bar/trust-bar.css');

$contactUrl = route('contact');
?>
<section class="band trust-bar" id="engineering-trust" aria-labelledby="trust-title"
    style="--trust-bg: url('<?= e(asset('images/lifestyle/blueprint-drafting.jpg')) ?>')">
    <div class="trust-inner">
        <div class="trust-head">
            <div class="trust-heading">
                <div class="trust-kicker-badge">
                    <span class="trust-badge-dot"></span>
                    <span class="trust-kicker"><?= e(trans('home.trust_kicker')) ?></span>
                </div>
                <h2 id="trust-title">
                    <?= e(trans('home.trust_title_before')) ?> <em><?= e(trans('home.trust_title_emphasis')) ?></em> <?= e(trans('home.trust_title_after')) ?>
                </h2>
            </div>
            <a class="trust-contact-link" href="<?= e($contactUrl) ?>">
                <span><?= e(trans('nav.contact')) ?></span>
                <svg viewBox="0 0 20 20" fill="currentColor" width="15" height="15" aria-hidden="true"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
            </a>
        </div>

        <div class="trust-grid">
            <!-- 01. Warranty & Structural Load -->
            <article class="trust-card">
                <div class="trust-card-top">
                    <div class="trust-stat">
                        <strong>10</strong><span><?= e(trans('home.year')) ?></span>
                    </div>
                    <span class="trust-card-index">01</span>
                </div>
                <h3><?= e(trans('home.trust_guarantee_title')) ?></h3>
                <p><?= e(trans('home.trust_guarantee_desc')) ?></p>
            </article>

            <!-- 02. Factory Direct & Water Economy -->
            <article class="trust-card">
                <div class="trust-card-top">
                    <div class="trust-stat">
                        <strong>45</strong><span>%</span>
                    </div>
                    <span class="trust-card-index">02</span>
                </div>
                <h3><?= e(trans('home.trust_water_title')) ?></h3>
                <p><?= e(trans('home.trust_water_desc')) ?></p>
            </article>

            <!-- 03. Vitreous Ceramic & Nano Glaze -->
            <article class="trust-card">
                <div class="trust-card-top">
                    <div class="trust-stat">
                        <strong>1,250</strong><span>°C</span>
                    </div>
                    <span class="trust-card-index">03</span>
                </div>
                <h3><?= e(trans('home.trust_ceramic_title')) ?></h3>
                <p><?= e(trans('home.trust_ceramic_desc')) ?></p>
            </article>

            <!-- 04. Logistics & Global Supply -->
            <article class="trust-card">
                <div class="trust-card-top">
                    <div class="trust-stat">
                        <strong>34</strong><span>+</span>
                    </div>
                    <span class="trust-card-index">04</span>
                </div>
                <h3><?= e(trans('home.trust_global_title')) ?></h3>
                <p><?= e(trans('home.trust_global_desc')) ?></p>
            </article>
        </div>
    </div>
</section>
