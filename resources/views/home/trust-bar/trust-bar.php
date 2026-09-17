<?php
/** @var Core\View\View $view */
$view->pushStyle('frontend/home/trust-bar/trust-bar.css');
?>
<section class="band trust-bar" aria-labelledby="trust-title">
    <div class="trust-inner">
        <div class="trust-heading">
            <span class="trust-kicker"><?= e(trans('home.trust_kicker')) ?></span>
            <h2 id="trust-title"><?= e(trans('home.trust_title_before')) ?> <em><?= e(trans('home.trust_title_emphasis')) ?></em> <?= e(trans('home.trust_title_after')) ?></h2>
            <div class="trust-heading-rule" aria-hidden="true"></div>
            <p><?= e(trans('home.trust_subtitle')) ?></p>
        </div>

        <div class="trust-grid">
            <article class="trust-card">
                <div class="trust-icon" aria-hidden="true">
                    <svg viewBox="0 0 48 48" role="img"><path d="M24 5 38 11v10c0 10-5.7 17.4-14 22-8.3-4.6-14-12-14-22V11l14-6Z"/><path d="m17 24 5 5 10-11"/></svg>
                </div>
                <div class="trust-stat"><strong>10</strong><span><?= e(trans('home.year')) ?></span></div>
                <span class="trust-accent" aria-hidden="true"></span>
                <h3><?= e(trans('home.trust_guarantee_title')) ?></h3>
                <p><?= e(trans('home.trust_guarantee_desc')) ?></p>
            </article>

            <article class="trust-card">
                <div class="trust-icon" aria-hidden="true">
                    <svg viewBox="0 0 48 48" role="img"><path d="M24 6c-2 7-10 13-10 22a10 10 0 0 0 20 0C34 19 26 13 24 6Z"/><path d="M20 32c-1-3 0-6 3-8"/></svg>
                </div>
                <div class="trust-stat"><strong>45</strong><span>%</span></div>
                <span class="trust-accent" aria-hidden="true"></span>
                <h3><?= e(trans('home.trust_water_title')) ?></h3>
                <p><?= e(trans('home.trust_water_desc')) ?></p>
            </article>

            <article class="trust-card">
                <div class="trust-icon" aria-hidden="true">
                    <svg viewBox="0 0 48 48" role="img"><path d="M24 7v24"/><path d="M19 12a5 5 0 1 1 10 0v18a9 9 0 1 1-10 0V12Z"/><circle cx="24" cy="36" r="3"/><path d="M32 15h4M32 21h4"/></svg>
                </div>
                <div class="trust-stat"><strong>1,250</strong><span>°C</span></div>
                <span class="trust-accent" aria-hidden="true"></span>
                <h3><?= e(trans('home.trust_ceramic_title')) ?></h3>
                <p><?= e(trans('home.trust_ceramic_desc')) ?></p>
            </article>

            <article class="trust-card">
                <div class="trust-icon" aria-hidden="true">
                    <svg viewBox="0 0 48 48" role="img"><circle cx="24" cy="24" r="16"/><path d="M8 24h32M24 8c5 5 7 10 7 16s-2 11-7 16M24 8c-5 5-7 10-7 16s2 11 7 16M10 16h28M10 32h28"/></svg>
                </div>
                <div class="trust-stat"><strong>34</strong><span>+</span></div>
                <span class="trust-accent" aria-hidden="true"></span>
                <h3><?= e(trans('home.trust_global_title')) ?></h3>
                <p><?= e(trans('home.trust_global_desc')) ?></p>
            </article>
        </div>

        <div class="trust-footer-line" aria-hidden="true"><span></span><small><?= e(trans('home.trust_footer')) ?></small><span></span></div>
    </div>
</section>
