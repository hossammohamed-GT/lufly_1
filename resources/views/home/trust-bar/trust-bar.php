<?php
/** @var Core\View\View $view */
$view->pushStyle('frontend/home/trust-bar/trust-bar.css');

$locale = isset($translator) && $translator instanceof \Core\Localization\Translator
    ? $translator->getLocale()
    : (string) config('localization.default', 'en');

$waMessage = rawurlencode("Hello LUFLY Team, I would like to request direct factory pricing and project specifications for architectural sanitary ware.");
$waUrl = "https://wa.me/908503040817?text={$waMessage}";
?>
<section class="band trust-bar scroll-section" id="engineering-trust" aria-labelledby="trust-title">
    <div class="trust-mesh" aria-hidden="true"></div>
    <div class="trust-inner">
        <div class="trust-heading scroll-reveal">
            <div class="trust-kicker-badge">
                <span class="trust-badge-dot"></span>
                <span class="trust-kicker"><?= e(trans('home.trust_kicker')) ?></span>
            </div>
            <h2 id="trust-title">
                <?= e(trans('home.trust_title_before')) ?> <em><?= e(trans('home.trust_title_emphasis')) ?></em> <?= e(trans('home.trust_title_after')) ?>
            </h2>
            <p><?= e(trans('home.trust_subtitle')) ?></p>
        </div>

        <div class="trust-grid">
            <article class="trust-card scroll-reveal" data-delay="1">
                <div class="trust-card-top">
                    <span class="trust-card-badge"><?= e(trans('home.trust_badge_1')) ?></span>
                    <div class="trust-icon" aria-hidden="true">
                        <svg viewBox="0 0 48 48" role="img"><path d="M24 5 38 11v10c0 10-5.7 17.4-14 22-8.3-4.6-14-12-14-22V11l14-6Z"/><path d="m17 24 5 5 10-11"/></svg>
                    </div>
                </div>
                <div class="trust-stat">
                    <strong>10</strong><span><?= e(trans('home.year')) ?></span>
                </div>
                <h3><?= e(trans('home.trust_guarantee_title')) ?></h3>
                <p><?= e(trans('home.trust_guarantee_desc')) ?></p>
                <div class="trust-card-shine" aria-hidden="true"></div>
            </article>

            <article class="trust-card scroll-reveal" data-delay="2">
                <div class="trust-card-top">
                    <span class="trust-card-badge"><?= e(trans('home.trust_badge_2')) ?></span>
                    <div class="trust-icon" aria-hidden="true">
                        <svg viewBox="0 0 48 48" role="img"><path d="M24 6c-2 7-10 13-10 22a10 10 0 0 0 20 0C34 19 26 13 24 6Z"/><path d="M20 32c-1-3 0-6 3-8"/></svg>
                    </div>
                </div>
                <div class="trust-stat">
                    <strong>45</strong><span>%</span>
                </div>
                <h3><?= e(trans('home.trust_water_title')) ?></h3>
                <p><?= e(trans('home.trust_water_desc')) ?></p>
                <div class="trust-card-shine" aria-hidden="true"></div>
            </article>

            <article class="trust-card scroll-reveal" data-delay="3">
                <div class="trust-card-top">
                    <span class="trust-card-badge"><?= e(trans('home.trust_badge_3')) ?></span>
                    <div class="trust-icon" aria-hidden="true">
                        <svg viewBox="0 0 48 48" role="img"><path d="M24 7v24"/><path d="M19 12a5 5 0 1 1 10 0v18a9 9 0 1 1-10 0V12Z"/><circle cx="24" cy="36" r="3"/><path d="M32 15h4M32 21h4"/></svg>
                    </div>
                </div>
                <div class="trust-stat">
                    <strong>1,250</strong><span>°C</span>
                </div>
                <h3><?= e(trans('home.trust_ceramic_title')) ?></h3>
                <p><?= e(trans('home.trust_ceramic_desc')) ?></p>
                <div class="trust-card-shine" aria-hidden="true"></div>
            </article>

            <article class="trust-card scroll-reveal" data-delay="4">
                <div class="trust-card-top">
                    <span class="trust-card-badge"><?= e(trans('home.trust_badge_4')) ?></span>
                    <div class="trust-icon" aria-hidden="true">
                        <svg viewBox="0 0 48 48" role="img"><circle cx="24" cy="24" r="16"/><path d="M8 24h32M24 8c5 5 7 10 7 16s-2 11-7 16M24 8c-5 5-7 10-7 16s2 11 7 16M10 16h28M10 32h28"/></svg>
                    </div>
                </div>
                <div class="trust-stat">
                    <strong>34</strong><span>+</span>
                </div>
                <h3><?= e(trans('home.trust_global_title')) ?></h3>
                <p><?= e(trans('home.trust_global_desc')) ?></p>
                <div class="trust-card-shine" aria-hidden="true"></div>
            </article>
        </div>

        <!-- High-Converting Action & Trust Banner -->
        <div class="trust-action-banner scroll-reveal">
            <div class="trust-action-info">
                <h4><?= e(trans('home.trust_cta_heading')) ?></h4>
                <p><?= e(trans('home.trust_cta_sub')) ?></p>
                <div class="trust-pills">
                    <span class="trust-pill"><svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg> <?= e(trans('home.trust_pill_1')) ?></span>
                    <span class="trust-pill"><svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg> <?= e(trans('home.trust_pill_2')) ?></span>
                    <span class="trust-pill"><svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg> <?= e(trans('home.trust_pill_3')) ?></span>
                    <span class="trust-pill"><svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg> <?= e(trans('home.trust_pill_4')) ?></span>
                </div>
            </div>
            <div class="trust-action-buttons">
                <a href="<?= e($waUrl) ?>" target="_blank" rel="noopener noreferrer" class="trust-btn-primary">
                    <svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91C2.13 13.66 2.59 15.36 3.45 16.86L2.05 22L7.3 20.62C8.75 21.41 10.38 21.83 12.04 21.83C17.5 21.83 21.95 17.38 21.95 11.92C21.95 9.27 20.92 6.78 19.05 4.91C17.18 3.03 14.69 2 12.04 2ZM12.05 20.15C10.56 20.15 9.11 19.76 7.85 19.01L7.55 18.83L4.43 19.65L5.26 16.61L5.06 16.29C4.24 14.99 3.81 13.47 3.81 11.91C3.81 7.37 7.5 3.68 12.04 3.68C14.25 3.68 16.31 4.54 17.87 6.1C19.42 7.66 20.28 9.72 20.28 11.92C20.28 16.46 16.59 20.15 12.05 20.15ZM16.57 14.43C16.32 14.3 15.1 13.71 14.88 13.62C14.65 13.54 14.48 13.5 14.32 13.75C14.15 14 13.67 14.57 13.52 14.74C13.38 14.9 13.23 14.92 12.98 14.8C12.73 14.67 11.94 14.41 11 13.57C10.27 12.91 9.78 12.1 9.63 11.85C9.48 11.6 9.61 11.47 9.74 11.34C9.85 11.23 9.99 11.05 10.12 10.9C10.24 10.75 10.28 10.64 10.36 10.48C10.44 10.31 10.4 10.17 10.34 10.05C10.28 9.92 9.78 8.7 9.58 8.19C9.38 7.69 9.18 7.76 9.03 7.75C8.89 7.74 8.72 7.74 8.56 7.74C8.39 7.74 8.12 7.8 7.89 8.05C7.67 8.3 7.03 8.9 7.03 10.12C7.03 11.34 7.92 12.52 8.04 12.68C8.17 12.85 9.78 15.33 12.26 16.4C12.85 16.65 13.31 16.81 13.67 16.92C14.26 17.11 14.8 17.08 15.22 17.02C15.7 16.95 16.67 16.43 16.88 15.86C17.08 15.29 17.08 14.8 17.02 14.7C16.96 14.6 16.81 14.55 16.57 14.43Z"/></svg>
                    <span><?= e(trans('home.trust_cta_quote')) ?></span>
                </a>
                <a href="<?= e(url('/' . $locale . '/products')) ?>" class="trust-btn-secondary">
                    <span><?= e(trans('home.trust_cta_catalog')) ?></span>
                    <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                </a>
            </div>
        </div>

        <div class="trust-footer-line" aria-hidden="true">
            <span></span>
            <small><?= e(trans('home.trust_footer')) ?></small>
            <span></span>
        </div>
    </div>
</section>
