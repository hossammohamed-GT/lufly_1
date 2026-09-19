<?php
/** @var Core\View\View $view */
$view->pushStyle('frontend/home/categories/categories.css');
?>
<section class="categories-section scroll-section" id="categories">
    <div class="container">
        <div class="section-header-center scroll-reveal">
            <span class="section-tag"><?= e(trans('home.collections_tag')) ?></span>
            <h2 class="section-title"><?= e(trans('home.design_title')) ?></h2>
            <p class="section-subtitle"><?= e(trans('home.design_desc')) ?></p>
        </div>

        <div class="category-mosaic">
            <!-- 1. Wall-Hung Toilets -->
            <a href="<?= e(route('products.index', ['category' => 'wall-hung-toilets'])) ?>" class="category-card-monolith scroll-reveal" data-delay="1">
                <img src="<?= e(asset('images/products/prod_146_1620-111-a.jpg')) ?>" alt="<?= e(trans('home.category_toilets')) ?>" loading="lazy" class="category-card-bg">
                <div class="category-card-gradient"></div>
                <div class="category-card-info">
                    <h3><?= e(trans('home.category_toilets')) ?></h3>
                    <p><?= e(trans('home.category_toilets_desc')) ?></p>
                </div>
            </a>

            <!-- 2. Washbasins -->
            <a href="<?= e(route('products.index', ['category' => 'designer-washbasins'])) ?>" class="category-card-monolith scroll-reveal" data-delay="2">
                <img src="<?= e(asset('images/products/prod_180_1610-242-65.jpg')) ?>" alt="<?= e(trans('home.category_basins')) ?>" loading="lazy" class="category-card-bg">
                <div class="category-card-gradient"></div>
                <div class="category-card-info">
                    <h3><?= e(trans('home.category_basins')) ?></h3>
                    <p><?= e(trans('home.category_basins_desc')) ?></p>
                </div>
            </a>

            <!-- 3. Faucets & Mixers -->
            <a href="<?= e(route('products.index', ['category' => 'architectural-ceramics'])) ?>" class="category-card-monolith scroll-reveal" data-delay="3">
                <img src="<?= e(asset('images/finishes/brushed-gold.jpg')) ?>" alt="<?= e(trans('home.category_mixers')) ?>" loading="lazy" class="category-card-bg">
                <div class="category-card-gradient"></div>
                <div class="category-card-info">
                    <h3><?= e(trans('home.category_mixers')) ?></h3>
                    <p><?= e(trans('home.category_mixers_desc')) ?></p>
                </div>
            </a>

            <!-- 4. Showers & Wellness -->
            <a href="<?= e(route('products.index')) ?>" class="category-card-monolith scroll-reveal" data-delay="4">
                <img src="<?= e(asset('images/lifestyle/luxury-shower.png')) ?>" alt="<?= e(trans('home.category_showers')) ?>" loading="lazy" class="category-card-bg">
                <div class="category-card-gradient"></div>
                <div class="category-card-info">
                    <h3><?= e(trans('home.category_showers')) ?></h3>
                    <p><?= e(trans('home.category_showers_desc')) ?></p>
                </div>
            </a>

            <!-- 5. Commercial Sanitary -->
            <a href="<?= e(route('products.index', ['category' => 'vanity-cabinets'])) ?>" class="category-card-monolith scroll-reveal" data-delay="5">
                <img src="<?= e(asset('images/products/prod_2050_1690-000.jpg')) ?>" alt="<?= e(trans('home.category_commercial')) ?>" loading="lazy" class="category-card-bg">
                <div class="category-card-gradient"></div>
                <div class="category-card-info">
                    <h3><?= e(trans('home.category_commercial')) ?></h3>
                    <p><?= e(trans('home.category_commercial_desc')) ?></p>
                </div>
            </a>
        </div>
    </div>
</section>
