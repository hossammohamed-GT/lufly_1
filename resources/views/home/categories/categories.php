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
$view->pushScript('frontend/home/categories/categories.js');

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
                /* the shots the controller picked from this category; the
                   category's own image (and then the logo) is the fallback */
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
                    <!-- the shot stage is the product-card frame: 7/5, a white
                         stage, `contain`. The category photographs are cut-outs
                         shot on white, and a `cover` crop of one is what used to
                         cut the fixture off - the wide ones were cropped to a
                         detail, the tall ones to a sliver. Extra shots of the
                         same category cycle (see categories.js). -->
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
    </div>
</section>
