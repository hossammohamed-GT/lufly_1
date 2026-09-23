<?php
/**
 * Home component: category mosaic.
 *
 * Fully data driven: the tiles are the real catalog categories imported from
 * the legacy LUFLY system (categories + category_translations), passed in by
 * HomeController. No hardcoded category slugs, names or images.
 *
 * @var Core\View\View $view
 * @var array<int, array<string, mixed>> $categories
 */
$view->pushStyle('frontend/home/categories/categories.css');

/** @var array<int, array<string, mixed>> $items */
$items = array_slice(is_array($categories ?? null) ? $categories : [], 0, 5);

if ($items === []) {
    /* Nothing to show only when the catalogue really has no active category -
       index.php hands the list over; a missing hand-off is a bug, and the
       debug line makes that obvious instead of rendering a silent gap. */
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
                ?>
                <a href="<?= e(route('products.index', ['category' => $slug])) ?>"
                   class="category-card-monolith scroll-reveal"
                   data-delay="<?= (int) $index + 1 ?>">
                    <img src="<?= e(asset(ltrim($image, '/'))) ?>" alt="<?= e($name) ?>" loading="lazy" class="category-card-bg">
                    <div class="category-card-gradient"></div>
                    <div class="category-card-info">
                        <h3><?= e($name) ?></h3>
                        <?php if ($description !== ''): ?>
                            <p><?= e($description) ?></p>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
