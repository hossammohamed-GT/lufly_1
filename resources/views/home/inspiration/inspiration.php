<?php
/** @var Core\View\View $view */
$view->pushStyle('frontend/home/inspiration/inspiration.css');

/* Each room links into the catalogue search for the fixture it showcases.
   These are real product terms (verified against the catalogue), not the
   poetic room titles, so the results page is never empty. */
$searchTerms = ['Bath Mixer', 'Washbasin Mixer', 'Washbasin', 'Sink Mixer'];
?>
<section class="inspiration-section scroll-section" id="inspiration">
    <div class="container">
        <div class="section-header-center scroll-reveal">
            <span class="section-tag"><?= e(trans('home.inspiration_tag')) ?></span>
            <h2 class="section-title"><?= e(trans('home.inspiration_title')) ?></h2>
            <p class="section-subtitle"><?= e(trans('home.inspiration_desc')) ?></p>
        </div>

        <div class="inspiration-carousel">
            <!-- Room 1 -->
            <a href="<?= e(route('products.index', ['q' => $searchTerms[0]])) ?>"
               class="inspiration-card scroll-reveal" data-delay="1"
               aria-label="<?= e(trans('home.inspiration_1_title')) ?>">
                <svg class="inspiration-trace" aria-hidden="true" focusable="false">
                    <rect class="inspiration-trace-rect" x="0" y="0" width="100%" height="100%" rx="10" pathLength="1" />
                </svg>
                <div class="inspiration-img-wrap">
                    <img src="<?= e(asset('images/lifestyle/spa-suite.jpg')) ?>" alt="<?= e(trans('home.inspiration_1_title')) ?>" loading="lazy" decoding="async" class="inspiration-img">
                </div>
                <div class="inspiration-body">
                    <span class="inspiration-tag"><?= e(trans('home.inspiration_1_tag')) ?></span>
                    <h4><?= e(trans('home.inspiration_1_title')) ?></h4>
                    <p><?= e(trans('home.inspiration_1_desc')) ?></p>
                </div>
            </a>

            <!-- Room 2 -->
            <a href="<?= e(route('products.index', ['q' => $searchTerms[1]])) ?>"
               class="inspiration-card scroll-reveal" data-delay="2"
               aria-label="<?= e(trans('home.inspiration_2_title')) ?>">
                <svg class="inspiration-trace" aria-hidden="true" focusable="false">
                    <rect class="inspiration-trace-rect" x="0" y="0" width="100%" height="100%" rx="10" pathLength="1" />
                </svg>
                <div class="inspiration-img-wrap">
                    <img src="<?= e(asset('images/lifestyle/modern-bathroom.png')) ?>" alt="<?= e(trans('home.inspiration_2_title')) ?>" loading="lazy" decoding="async" class="inspiration-img">
                </div>
                <div class="inspiration-body">
                    <span class="inspiration-tag"><?= e(trans('home.inspiration_2_tag')) ?></span>
                    <h4><?= e(trans('home.inspiration_2_title')) ?></h4>
                    <p><?= e(trans('home.inspiration_2_desc')) ?></p>
                </div>
            </a>

            <!-- Room 3 -->
            <a href="<?= e(route('products.index', ['q' => $searchTerms[2]])) ?>"
               class="inspiration-card scroll-reveal" data-delay="3"
               aria-label="<?= e(trans('home.inspiration_3_title')) ?>">
                <svg class="inspiration-trace" aria-hidden="true" focusable="false">
                    <rect class="inspiration-trace-rect" x="0" y="0" width="100%" height="100%" rx="10" pathLength="1" />
                </svg>
                <div class="inspiration-img-wrap">
                    <img src="<?= e(asset('images/lifestyle/minimal-basin.png')) ?>" alt="<?= e(trans('home.inspiration_3_title')) ?>" loading="lazy" decoding="async" class="inspiration-img">
                </div>
                <div class="inspiration-body">
                    <span class="inspiration-tag"><?= e(trans('home.inspiration_3_tag')) ?></span>
                    <h4><?= e(trans('home.inspiration_3_title')) ?></h4>
                    <p><?= e(trans('home.inspiration_3_desc')) ?></p>
                </div>
            </a>

            <!-- Room 4 -->
            <a href="<?= e(route('products.index', ['q' => $searchTerms[3]])) ?>"
               class="inspiration-card scroll-reveal" data-delay="4"
               aria-label="<?= e(trans('home.inspiration_4_title')) ?>">
                <svg class="inspiration-trace" aria-hidden="true" focusable="false">
                    <rect class="inspiration-trace-rect" x="0" y="0" width="100%" height="100%" rx="10" pathLength="1" />
                </svg>
                <div class="inspiration-img-wrap">
                    <img src="<?= e(asset('images/lifestyle/kitchen-suite.png')) ?>" alt="<?= e(trans('home.inspiration_4_title')) ?>" loading="lazy" decoding="async" class="inspiration-img">
                </div>
                <div class="inspiration-body">
                    <span class="inspiration-tag"><?= e(trans('home.inspiration_4_tag')) ?></span>
                    <h4><?= e(trans('home.inspiration_4_title')) ?></h4>
                    <p><?= e(trans('home.inspiration_4_desc')) ?></p>
                </div>
            </a>
        </div>
    </div>
</section>
