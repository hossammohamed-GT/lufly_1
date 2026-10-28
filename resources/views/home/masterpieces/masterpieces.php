<?php
/** @var Core\View\View $view */
$view->pushDeferredStyle('frontend/css/product-card.css');
$view->pushDeferredStyle('frontend/home/masterpieces/masterpieces.css');
$view->pushScript('frontend/js/product-card-variants.js');
$view->pushScript('frontend/products/catalog/catalog.js');
$view->pushDeferredStyle('frontend/components/skeleton/skeleton.css');
$view->pushScript('frontend/components/skeleton/skeleton.js');
if (feature('favorites', true)) {
    $view->pushDeferredStyle('frontend/favorites/favorites.css');
    $view->pushScript('frontend/favorites/favorites.js');
}
if (feature('box', true)) {
    $view->pushDeferredStyle('frontend/box/box.css');
    $view->pushScript('frontend/box/box.js');
}
/** @var array $featuredProducts */
/** @var array<int, int|string> $savedIds */
/** @var array<int, int|string> $boxIds */
$featured = is_array($featuredProducts ?? null) ? $featuredProducts : [];
$savedMap = is_array($savedIds ?? null) ? $savedIds : [];
$boxMap = is_array($boxIds ?? null) ? $boxIds : [];
?>
<section class="masterpieces-section scroll-section" id="featured">
    <div class="container">
        <div class="row-end masterpieces-head scroll-reveal">
            <div>
                <span class="section-tag"><?= e(trans('home.featured_tag')) ?></span>
                <h2 class="section-title"><?= e(trans('home.featured_title')) ?></h2>
            </div>
            <a href="<?= e(route('products.index')) ?>" class="masterpieces-all">
                <span><?= e(trans('home.full_catalog')) ?></span>
                <svg viewBox="0 0 20 20" fill="currentColor" width="14" height="14" aria-hidden="true"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
            </a>
        </div>

        <!-- database driven: a glass skeleton holds the shape while the
             product imagery decodes, so this block never blocks first paint -->
        <div data-sk-host>
            <div class="sk-grid" data-sk-skeleton aria-hidden="true">
                <?php for ($i = 0; $i < 4; $i++): ?>
                    <div class="sk sk-card">
                        <div class="sk-card-media"></div>
                        <div class="sk-card-body">
                            <div class="sk-card-top">
                                <span class="sk-line"></span>
                                <span class="sk-line"></span>
                            </div>
                            <div class="sk-line is-lg w-85"></div>
                            <div class="sk-line is-sm w-55"></div>
                        </div>
                    </div>
                <?php endfor; ?>
            </div>

            <div class="masterpieces-grid catalog-grid" data-sk-real>
                <?php foreach (array_slice($featured, 0, 4) as $idx => $product): ?>
                    <?php $pid = (int) ($product['id'] ?? 0); ?>
                    <?= $view->component('product-card', [
                        'product' => $product,
                        'slug' => (string) ($product['slug'] ?? ''),
                        'index' => $idx,
                        'heading' => 'h3',
                        'saved' => isset($savedMap[$pid]),
                        'in_box' => isset($boxMap[$pid]),
                    ]) ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
