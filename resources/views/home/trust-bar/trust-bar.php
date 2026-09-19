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
            <article class="trust-item">
                <div class="trust-item-top">
                    <span class="trust-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 20 6.2v5.3c0 4.9-3.4 8.6-8 10.5-4.6-1.9-8-5.6-8-10.5V6.2L12 3Z"/><path d="m8.8 12 2.2 2.2 4.2-4.7"/></svg>
                    </span>
                    <span class="trust-card-index">01</span>
                </div>
                <div class="trust-stat">
                    <strong>10</strong><span><?= e(trans('home.year')) ?></span>
                </div>
                <h3><?= e(trans('home.trust_guarantee_title')) ?></h3>
                <p><?= e(trans('home.trust_guarantee_desc')) ?></p>
            </article>

            <!-- 02. Factory Direct & Water Economy -->
            <article class="trust-item">
                <div class="trust-item-top">
                    <span class="trust-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3c-2.4 3.2-6.5 7-6.5 11a6.5 6.5 0 0 0 13 0C18.5 10 14.4 6.2 12 3Z"/><path d="M9.5 14.5c-.4-1.6.3-3.2 1.6-4.2"/></svg>
                    </span>
                    <span class="trust-card-index">02</span>
                </div>
                <div class="trust-stat">
                    <strong>45</strong><span>%</span>
                </div>
                <h3><?= e(trans('home.trust_water_title')) ?></h3>
                <p><?= e(trans('home.trust_water_desc')) ?></p>
            </article>

            <!-- 03. Vitreous Ceramic & Nano Glaze -->
            <article class="trust-item">
                <div class="trust-item-top">
                    <span class="trust-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3c1 3-4 5.2-4 9.6a4 4 0 0 0 8 0c0-1.8-.8-3.1-1.6-4.4-.6 1-1.4 1.6-2.4 2 .6-2.4.6-5 0-7.2Z"/><path d="M12 20.5c3.6 0 6.5-.6 6.5-1.4 0-.6-1.6-1.1-4-1.3M12 20.5c-3.6 0-6.5-.6-6.5-1.4 0-.6 1.6-1.1 4-1.3"/></svg>
                    </span>
                    <span class="trust-card-index">03</span>
                </div>
                <div class="trust-stat">
                    <strong>1,250</strong><span>°C</span>
                </div>
                <h3><?= e(trans('home.trust_ceramic_title')) ?></h3>
                <p><?= e(trans('home.trust_ceramic_desc')) ?></p>
            </article>

            <!-- 04. Logistics & Global Supply -->
            <article class="trust-item">
                <div class="trust-item-top">
                    <span class="trust-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="8.5"/><path d="M3.5 12h17M12 3.5c2.6 2.4 3.9 5.2 3.9 8.5s-1.3 6.1-3.9 8.5c-2.6-2.4-3.9-5.2-3.9-8.5s1.3-6.1 3.9-8.5Z"/><path d="M5.5 7.5h13M5.5 16.5h13"/></svg>
                    </span>
                    <span class="trust-card-index">04</span>
                </div>
                <div class="trust-stat">
                    <strong>34</strong><span>+</span>
                </div>
                <h3><?= e(trans('home.trust_global_title')) ?></h3>
                <p><?= e(trans('home.trust_global_desc')) ?></p>
            </article>
        </div>
    </div>
</section>
