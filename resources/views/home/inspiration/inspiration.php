<?php
/** @var Core\View\View $view */
$view->pushStyle('frontend/home/inspiration/inspiration.css');
?>
<section class="inspiration-section" id="inspiration">
    <div class="container">
        <div class="section-header-center">
            <span class="section-tag"><?= e(trans('home.inspiration_tag')) ?></span>
            <h2 class="section-title"><?= e(trans('home.inspiration_title')) ?></h2>
            <p class="section-subtitle"><?= e(trans('home.inspiration_desc')) ?></p>
        </div>

        <div class="inspiration-carousel">
            <!-- Room 1 -->
            <div class="inspiration-card">
                <div class="inspiration-img-wrap">
                    <img src="<?= e(asset('images/lifestyle/spa-suite.jpg')) ?>" alt="The Nordic Spa Suite" class="inspiration-img">
                </div>
                <div class="inspiration-body">
                    <span class="inspiration-tag"><?= e(trans('home.inspiration_1_tag')) ?></span>
                    <h4><?= e(trans('home.inspiration_1_title')) ?></h4>
                    <p><?= e(trans('home.inspiration_1_desc')) ?></p>
                </div>
            </div>

            <!-- Room 2 -->
            <div class="inspiration-card">
                <div class="inspiration-img-wrap">
                    <img src="<?= e(asset('images/lifestyle/modern-bathroom.png')) ?>" alt="Modern Bathroom" class="inspiration-img">
                </div>
                <div class="inspiration-body">
                    <span class="inspiration-tag"><?= e(trans('home.inspiration_2_tag')) ?></span>
                    <h4><?= e(trans('home.inspiration_2_title')) ?></h4>
                    <p><?= e(trans('home.inspiration_2_desc')) ?></p>
                </div>
            </div>

            <!-- Room 3 -->
            <div class="inspiration-card">
                <div class="inspiration-img-wrap">
                    <img src="<?= e(asset('images/lifestyle/minimal-basin.png')) ?>" alt="Powder Room" class="inspiration-img">
                </div>
                <div class="inspiration-body">
                    <span class="inspiration-tag"><?= e(trans('home.inspiration_3_tag')) ?></span>
                    <h4><?= e(trans('home.inspiration_3_title')) ?></h4>
                    <p><?= e(trans('home.inspiration_3_desc')) ?></p>
                </div>
            </div>

            <!-- Room 4 -->
            <div class="inspiration-card">
                <div class="inspiration-img-wrap">
                    <img src="<?= e(asset('images/lifestyle/kitchen-suite.png')) ?>" alt="Kitchen Suite" class="inspiration-img">
                </div>
                <div class="inspiration-body">
                    <span class="inspiration-tag"><?= e(trans('home.inspiration_4_tag')) ?></span>
                    <h4><?= e(trans('home.inspiration_4_title')) ?></h4>
                    <p><?= e(trans('home.inspiration_4_desc')) ?></p>
                </div>
            </div>
        </div>
    </div>
</section>
