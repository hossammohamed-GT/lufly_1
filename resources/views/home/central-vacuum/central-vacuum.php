<?php
/** @var Core\View\View $view */
$view->pushDeferredStyle('frontend/home/central-vacuum/central-vacuum.css');
$view->pushScript('frontend/home/central-vacuum/central-vacuum.js');
?>
<section class="cv-home-section" id="central-vacuum-preview" data-cv-home>
    <div class="container">
        <a class="cv-home-card" href="<?= e(route('central-vacuum')) ?>" aria-label="<?= e(trans('central_vacuum.home_cta')) ?>">
            <span class="cv-home-orbit cv-home-orbit--one" aria-hidden="true"></span>
            <span class="cv-home-orbit cv-home-orbit--two" aria-hidden="true"></span>

            <div class="cv-home-copy">
                <span class="section-tag cv-home-tag"><?= e(trans('central_vacuum.home_kicker')) ?></span>
                <h2 class="cv-home-title"><?= e(trans('central_vacuum.home_title')) ?></h2>
                <p class="cv-home-desc"><?= e(trans('central_vacuum.home_desc')) ?></p>

                <div class="cv-home-pills" aria-label="<?= e(trans('central_vacuum.home_kicker')) ?>">
                    <span><?= e(trans('central_vacuum.home_micro_1')) ?></span>
                    <span><?= e(trans('central_vacuum.home_micro_2')) ?></span>
                    <span><?= e(trans('central_vacuum.home_micro_3')) ?></span>
                </div>

                <span class="cv-home-link">
                    <?= e(trans('central_vacuum.home_cta')) ?>
                    <svg class="icon" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M5 12h14"></path>
                        <path d="m13 6 6 6-6 6"></path>
                    </svg>
                </span>
            </div>

            <div class="cv-home-visual" aria-hidden="true">
                <div class="cv-home-console">
                    <span></span><span></span><span></span>
                </div>
                <svg class="cv-home-svg" viewBox="0 0 560 360" role="img" focusable="false">
                    <defs>
                        <linearGradient id="cvh-pipe-gradient" x1="0" x2="1" y1="0" y2="1">
                            <stop offset="0" stop-color="var(--lu-mint)"></stop>
                            <stop offset="1" stop-color="var(--ds-primary)"></stop>
                        </linearGradient>
                        <filter id="cvh-glow" x="-30%" y="-30%" width="160%" height="160%">
                            <feGaussianBlur stdDeviation="4" result="blur"></feGaussianBlur>
                            <feMerge>
                                <feMergeNode in="blur"></feMergeNode>
                                <feMergeNode in="SourceGraphic"></feMergeNode>
                            </feMerge>
                        </filter>
                    </defs>

                    <path class="cvh-grid" d="M40 292H500M60 70H480M80 54v250M210 54v250M340 54v250M470 54v250"></path>
                    <path class="cvh-house" d="M74 284V116l206-70 206 70v168Z"></path>
                    <path class="cvh-room" d="M74 174h412M204 94v190M344 94v190"></path>

                    <path id="cvh-route-living" class="cvh-pipe" data-cvh-draw pathLength="1" d="M128 226 C174 226 182 185 226 185 S282 189 306 204 360 238 432 238"></path>
                    <path id="cvh-route-bedroom" class="cvh-pipe" data-cvh-draw pathLength="1" d="M138 142 C176 126 208 142 236 162 S285 212 432 238"></path>
                    <path id="cvh-route-kitchen" class="cvh-pipe" data-cvh-draw pathLength="1" d="M404 143 C388 164 373 186 361 207 S387 238 432 238"></path>
                    <path class="cvh-pipe cvh-pipe--main" data-cvh-draw pathLength="1" d="M432 238 C468 248 482 264 490 296"></path>

                    <g class="cvh-unit" transform="translate(456 264)">
                        <rect x="0" y="0" width="68" height="68" rx="18"></rect>
                        <circle cx="34" cy="24" r="10"></circle>
                        <path d="M18 48h32"></path>
                    </g>

                    <g class="cvh-inlets" filter="url(#cvh-glow)">
                        <circle cx="128" cy="226" r="9"></circle>
                        <circle cx="138" cy="142" r="9"></circle>
                        <circle cx="404" cy="143" r="9"></circle>
                    </g>

                    <g class="cvh-flow" filter="url(#cvh-glow)">
                        <circle r="4"><animateMotion dur="4.2s" repeatCount="indefinite" rotate="auto"><mpath href="#cvh-route-living"></mpath></animateMotion></circle>
                        <circle r="3"><animateMotion dur="5.1s" begin=".8s" repeatCount="indefinite" rotate="auto"><mpath href="#cvh-route-bedroom"></mpath></animateMotion></circle>
                        <circle r="3.5"><animateMotion dur="4.7s" begin="1.4s" repeatCount="indefinite" rotate="auto"><mpath href="#cvh-route-kitchen"></mpath></animateMotion></circle>
                    </g>
                </svg>
                <div class="cv-home-label cv-home-label--rooms"><?= e(trans('central_vacuum.home_label_rooms')) ?></div>
                <div class="cv-home-label cv-home-label--unit"><?= e(trans('central_vacuum.home_label_unit')) ?></div>
            </div>
        </a>
    </div>
</section>
