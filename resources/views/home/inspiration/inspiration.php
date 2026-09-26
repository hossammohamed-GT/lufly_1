<?php
/** @var Core\View\View $view */
$view->pushDeferredStyle('frontend/home/inspiration/inspiration.css');

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
                    <?= $view->component('responsive-image', [
                        'src'    => 'images/lifestyle/spa-suite.jpg',
                        'alt'    => trans('home.inspiration_1_title'),
                        'class'  => 'inspiration-img',
                        /* 4 columns > 1024px, 2 down to 640px, 1 below that */
                        'sizes'  => '(max-width: 640px) 92vw, (max-width: 1024px) 46vw, 23vw',
                    ]) ?>
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
                    <?= $view->component('responsive-image', [
                        'src'    => 'images/lifestyle/modern-bathroom.jpg',
                        'alt'    => trans('home.inspiration_2_title'),
                        'class'  => 'inspiration-img',
                        /* 4 columns > 1024px, 2 down to 640px, 1 below that */
                        'sizes'  => '(max-width: 640px) 92vw, (max-width: 1024px) 46vw, 23vw',
                    ]) ?>
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
                    <?= $view->component('responsive-image', [
                        'src'    => 'images/lifestyle/minimal-basin.jpg',
                        'alt'    => trans('home.inspiration_3_title'),
                        'class'  => 'inspiration-img',
                        /* 4 columns > 1024px, 2 down to 640px, 1 below that */
                        'sizes'  => '(max-width: 640px) 92vw, (max-width: 1024px) 46vw, 23vw',
                    ]) ?>
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
                    <?= $view->component('responsive-image', [
                        'src'    => 'images/lifestyle/kitchen-suite.jpg',
                        'alt'    => trans('home.inspiration_4_title'),
                        'class'  => 'inspiration-img',
                        /* 4 columns > 1024px, 2 down to 640px, 1 below that */
                        'sizes'  => '(max-width: 640px) 92vw, (max-width: 1024px) 46vw, 23vw',
                    ]) ?>
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
