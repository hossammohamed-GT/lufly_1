<?php
$view->pushStyle('frontend/home/categories/categories.css');
$view->pushScript('frontend/home/categories/categories.js');

$items = array_slice(is_array($categories ?? null) ? $categories : [], 0, 4);

if ($items === []) {
    if (config('app.debug', false)) {
        echo '<!-- home.categories: no categories passed to the component -->';
    }

    return;
}

$fallbackImg = '/images/logo.png';
?>
<section class="categories-section scroll-section" id="categories">
    <div class="container">
        <div class="section-header-center scroll-reveal">
            <span class="section-tag"><?= e(trans('home.collections_tag')) ?></span>
            <h2 class="section-title"><?= e(trans('home.design_title')) ?></h2>
            <p class="section-subtitle"><?= e(trans('home.design_desc')) ?></p>
        </div>

        <div class="category-mosaic">
            <?php foreach ($items as $index => $category): ?>
                <?php
                $slug = (string) ($category['slug'] ?? '');
                $name = (string) ($category['name'] ?? $slug);
                $image = (string) ($category['image'] ?? '') !== '' ? (string) $category['image'] : $fallbackImg;
                $description = trim((string) ($category['description'] ?? ''));
                $shots = [];
                foreach ((array) ($category['shots'] ?? []) as $shot) {
                    $shot = trim((string) $shot);
                    if ($shot !== '') {
                        $shots[] = $shot;
                    }
                }
                if ($shots === []) {
                    $shots = [$image];
                }
                ?>
                <a href="<?= e(route('products.index', ['category' => $slug])) ?>"
                   class="category-card-monolith scroll-reveal"
                   data-delay="<?= (int) $index + 1 ?>">
<div class="category-card-stage">
                        <?php foreach ($shots as $shotIndex => $shot): ?>
                            <img src="<?= e(asset(ltrim($shot, '/'))) ?>"
                                 alt="<?= $shotIndex === 0 ? e($name) : '' ?>"
                                 class="category-card-shot<?= $shotIndex === 0 ? ' is-on' : '' ?>"
                                 data-shot="<?= (int) $shotIndex ?>"
                                 loading="lazy" decoding="async"
                                 width="560" height="400"
                                 onerror="this.onerror=null; this.remove();">
                        <?php endforeach; ?>
                    </div>
                    <div class="category-card-info">
                        <h3><?= e($name) ?></h3>
                        <?php if ($description !== ''): ?>
                            <p><?= e($description) ?></p>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="categories-more-wrap scroll-reveal">
            <a href="<?= e(route('products.index')) ?>" class="categories-more">
                <span><?= e(trans('home.full_catalog')) ?></span>
                <svg viewBox="0 0 20 20" fill="currentColor" width="14" height="14" aria-hidden="true"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
            </a>
        </div>
    </div>
</section>
