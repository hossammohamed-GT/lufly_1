<?php
/** @var Core\View\View $view */
$view->layout('layouts.frontend');
/** @var Core\Database\Paginator $paginator */
/** @var array $categories */
/** @var string $activeCategory */
/** @var string $searchQuery */
/** @var string $locale */
$categories = $categories ?? [];
$activeCategory = $activeCategory ?? '';
$searchQuery = $searchQuery ?? '';
?>
<section class="container catalog-page">
    <header class="page-header catalog-header">
        <div class="header-titles">
            <span class="eyebrow"><?= e(trans('common.factory_direct')) ?></span>
            <h1 class="page-title"><?= e(trans('products.title')) ?></h1>
            <p class="page-subtitle"><?= e(trans('products.specs_title')) ?> (EN-997 & CE Certified)</p>
        </div>

        <form method="GET" action="<?= e(route('products.index')) ?>" class="search-form">
            <?php if ($activeCategory !== ''): ?>
                <input type="hidden" name="category" value="<?= e($activeCategory) ?>">
            <?php endif; ?>
            <div class="search-input-wrap">
                <input type="text" name="q" value="<?= e($searchQuery) ?>" placeholder="<?= e(trans('common.search')) ?>" class="input search-input">
                <button type="submit" class="btn btn-primary btn-sm"><?= e(trans('common.search')) ?></button>
                <?php if ($searchQuery !== '' || $activeCategory !== ''): ?>
                    <a href="<?= e(route('products.index')) ?>" class="btn btn-ghost btn-sm"><?= e(trans('common.cancel')) ?></a>
                <?php endif; ?>
            </div>
        </form>
    </header>

    <!-- Categories Filter Tabs -->
    <?php if (!empty($categories)): ?>
        <div class="category-tabs-nav">
            <a href="<?= e(route('products.index', $searchQuery !== '' ? ['q' => $searchQuery] : [])) ?>" 
               class="category-pill <?= $activeCategory === '' ? 'is-active' : '' ?>">
                <?= e(trans('common.all_categories')) ?>
            </a>
            <?php foreach ($categories as $cat): ?>
                <a href="<?= e(route('products.index', array_filter(['category' => $cat['slug'], 'q' => $searchQuery]))) ?>" 
                   class="category-pill <?= $activeCategory === $cat['slug'] ? 'is-active' : '' ?>">
                    <?= e($cat['name'] ?: $cat['slug']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($paginator->items() === []): ?>
        <div class="empty-state card">
            <div class="card-body" style="text-align: center; padding: var(--space-10);">
                <h3><?= e(trans('common.no_results')) ?></h3>
                <p><?= e(trans('products.empty')) ?></p>
                <a href="<?= e(route('products.index')) ?>" class="btn btn-primary"><?= e(trans('common.all_categories')) ?></a>
            </div>
        </div>
    <?php else: ?>
        <div class="grid grid-3 product-grid">
            <?php foreach ($paginator->items() as $product): 
                $item = $product->translate($locale);
                $img = $product->image ?: '/images/products/prod_146_1620-111-a.jpg';
                $waMsg = rawurlencode('Hello LUFLY, I am interested in B2B quotation for product: ' . ($item['name'] ?? '') . ' (SKU: ' . $product->sku . ')');
            ?>
                <article class="card product-card">
                    <div class="product-media">
                        <a href="<?= e(route('products.show', ['slug' => $product->slug])) ?>">
                            <img src="<?= e(asset($img)) ?>" 
                                 alt="<?= e($item['name'] ?? '') ?>" 
                                 loading="lazy" 
                                 class="product-image"
                                 onerror="this.onerror=null; this.src='<?= e(asset('public/images/products/prod_146_1620-111-a.jpg')) ?>';">
                        </a>
                        <span class="product-sku-badge"><?= e($product->sku) ?></span>
                    </div>
                    <div class="card-body">
                        <span class="product-badge-quality">Vitreous China • 10Y Guarantee</span>
                        <h2 class="card-title product-title">
                            <a href="<?= e(route('products.show', ['slug' => $product->slug])) ?>">
                                <?= e($item['name'] ?? '') ?>
                            </a>
                        </h2>
                        <p class="card-subtitle product-desc"><?= e($item['short_description'] ?? '') ?></p>
                    </div>
                    <div class="card-footer product-actions">
                        <a href="<?= e(route('products.show', ['slug' => $product->slug])) ?>" class="btn btn-secondary btn-sm">
                            <?= e(trans('products.view_details')) ?>
                        </a>
                        <a href="https://wa.me/908503040817?text=<?= $waMsg ?>" target="_blank" rel="noopener" class="btn btn-primary btn-sm btn-whatsapp">
                            <span>WhatsApp</span>
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <?php if ($paginator->lastPage() > 1): ?>
            <nav class="pagination">
                <?php 
                $queryParams = array_filter(['category' => $activeCategory, 'q' => $searchQuery]);
                for ($p = 1; $p <= $paginator->lastPage(); $p++): 
                    $pageUrl = '?' . http_build_query(array_merge($queryParams, ['page' => $p]));
                ?>
                    <a class="page-link <?= $p === $paginator->page() ? 'is-active' : '' ?>"
                       href="<?= e($pageUrl) ?>"><?= $p ?></a>
                <?php endfor; ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</section>
