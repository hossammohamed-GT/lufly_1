<?php
/**
 * Storefront product page - "machined editorial" identity.
 * Three essential views per product: main shot, live technical drawing
 * (dimension lines from product_dimensions when present), in-situ shot.
 *
 * @var Core\View\View $view
 */
$view->layout('layouts.frontend');
$view->pushStyle('frontend/products/detail/detail.css');
$view->pushScript('frontend/products/detail/detail.js');
/** @var array<string, mixed> $product */
/** @var string $locale */

$fallbackImg = '/images/products/prod_146_1620-111-a.jpg';
$img = (string) ($product['image'] ?? '') !== '' ? (string) $product['image'] : $fallbackImg;
$situImg = (string) ($product['situ_image'] ?? '');
$modelCode = (string) ($product['model_code'] ?? $product['sku'] ?? '');
$variants = is_array($product['variants'] ?? null) ? $product['variants'] : [];
$specs = is_array($product['specs'] ?? null) ? $product['specs'] : [];
/* three independent image groups; each may hold any number of images */
$gallery = is_array($product['gallery'] ?? null) ? array_values($product['gallery']) : [];
$drawings = is_array($product['drawings'] ?? null) ? array_values($product['drawings']) : [];
$situImages = is_array($product['situ_images'] ?? null) ? array_values($product['situ_images']) : [];

if ($situImages === [] && $situImg !== '') {
    $situImages = [$situImg];
}
if ($gallery === [] && $img !== '') {
    $gallery = [$img];
}

/* a tab only exists when that group actually has images */
$views = [];
if ($gallery !== []) {
    $views[] = ['key' => 'main', 'label' => trans('products.view_main'), 'images' => $gallery];
}
if ($drawings !== []) {
    $views[] = ['key' => 'drawing', 'label' => trans('products.view_drawing'), 'images' => $drawings];
}
if ($situImages !== []) {
    $views[] = ['key' => 'situ', 'label' => trans('products.view_situ'), 'images' => $situImages];
}
$dimensions = is_array($product['dimensions'] ?? null) ? $product['dimensions'] : null;
$categorySlug = (string) ($product['category_slug'] ?? '');
$productUrl = route('products.show', ['slug' => $product['slug'] ?? $slug]);

/* dimensions in millimetres, shown in the spec table when the factory has
   entered them */
$dims = [
    'w' => isset($dimensions['width_mm']) ? (float) $dimensions['width_mm'] : 0.0,
    'h' => isset($dimensions['height_mm']) ? (float) $dimensions['height_mm'] : 0.0,
    'd' => isset($dimensions['depth_mm']) ? (float) $dimensions['depth_mm'] : 0.0,
];
$hasDims = $dims['w'] > 0 && $dims['h'] > 0;
$fmt = static fn (float $v): string => rtrim(rtrim(number_format($v, 0, '.', ''), '0'), '.');

?>
<main class="pdp" id="main">
    <div class="pdp-inner">
        <nav class="pdp-crumb" aria-label="Breadcrumb">
            <a href="<?= e(route('home')) ?>"><?= e(trans('common.factory_direct')) ?></a>
            <span class="pdp-crumb-sep" aria-hidden="true">/</span>
            <a href="<?= e(route('products.index')) ?>"><?= e(trans('products.title')) ?></a>
            <span class="pdp-crumb-sep" aria-hidden="true">/</span>
            <span class="pdp-crumb-here"><?= e($product['name'] ?? '') ?></span>
        </nav>

        <div class="pdp-grid">
            <!-- ================= media stage: 3 essential views ================= -->
            <section class="pdp-stage" aria-label="<?= e(trans('products.sheet_title')) ?>">
                <span class="pdp-stage-beam" aria-hidden="true"></span>

                <?php foreach ($views as $vIndex => $v): ?>
                    <figure class="pdp-view<?= $vIndex === 0 ? ' is-on' : '' ?>"
                            data-pdp-view="<?= e($v['key']) ?>">
                        <div class="pdp-frameplate pdp-frameplate-<?= e($v['key']) ?>">
                            <?php foreach ($v['images'] as $iIndex => $src): ?>
                                <img src="<?= e(asset($src)) ?>"
                                     alt="<?= e(($product['name'] ?? '') . ' - ' . $v['label']) ?>"
                                     class="pdp-view-img<?= $v['key'] === 'situ' ? ' pdp-view-img-cover' : '' ?><?= $iIndex === 0 ? ' is-on' : '' ?>"
                                     data-pdp-slide="<?= (int) $iIndex ?>"
                                     width="900" height="700"
                                     <?= $vIndex === 0 && $iIndex === 0 ? 'decoding="async"' : 'loading="lazy" decoding="async"' ?>
                                     onerror="this.onerror=null; this.remove();">
                            <?php endforeach; ?>

                            <?php if ($v['key'] === 'drawing'): ?>
                                <!-- blueprint title block: keeps the drawing reading
                                     as a technical document, not a photo -->
                                <span class="pdp-plate-tick" aria-hidden="true"></span>
                                <div class="pdp-plate-block">
                                    <span class="pdp-plate-model">MODEL <?= e($modelCode) ?></span>
                                    <span class="pdp-plate-std">EN 997 &middot; CE</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if (count($v['images']) > 1): ?>
                            <button type="button" class="pdp-slide-nav pdp-slide-prev" data-pdp-prev
                                    aria-label="<?= e(trans('products.view_prev')) ?>">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" width="18" height="18" aria-hidden="true"><path d="m15 6-6 6 6 6"/></svg>
                            </button>
                            <button type="button" class="pdp-slide-nav pdp-slide-next" data-pdp-next
                                    aria-label="<?= e(trans('products.view_next')) ?>">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" width="18" height="18" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
                            </button>
                        <?php endif; ?>

                        <?php if (count($v['images']) > 1): ?>
                            <!-- thumbnail strip: makes it obvious at a glance that the
                                 product has more pictures, and jumps straight to one -->
                            <div class="pdp-thumbs" role="tablist" aria-label="<?= e($v['label']) ?>">
                                <?php foreach ($v['images'] as $dIndex => $thumb): ?>
                                    <button type="button"
                                            class="pdp-thumb<?= $dIndex === 0 ? ' is-on' : '' ?>"
                                            data-pdp-dot="<?= (int) $dIndex ?>"
                                            role="tab"
                                            aria-selected="<?= $dIndex === 0 ? 'true' : 'false' ?>"
                                            aria-label="<?= e($v['label']) ?> <?= (int) $dIndex + 1 ?>">
                                        <img src="<?= e(asset($thumb)) ?>"
                                             alt=""
                                             class="pdp-thumb-img"
                                             width="96" height="96"
                                             loading="lazy" decoding="async"
                                             onerror="this.onerror=null; this.closest('.pdp-thumb').remove();">
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <figcaption class="pdp-view-tag">
                            <?= e($v['label']) ?>
                            <?php if (count($v['images']) > 1): ?>
                                <span class="pdp-view-count" data-pdp-counter>1/<?= count($v['images']) ?></span>
                            <?php endif; ?>
                        </figcaption>
                    </figure>
                <?php endforeach; ?>

                <div class="pdp-viewtabs" role="tablist" aria-label="<?= e(trans('products.sheet_title')) ?>">
                    <?php foreach ($views as $vIndex => $v): ?>
                        <button type="button"
                                class="pdp-viewtab<?= $vIndex === 0 ? ' is-on' : '' ?>"
                                data-pdp-tab="<?= e($v['key']) ?>"
                                role="tab"
                                aria-selected="<?= $vIndex === 0 ? 'true' : 'false' ?>">
                            <?= e($v['label']) ?>
                            <?php if (count($v['images']) > 1): ?>
                                <span class="pdp-viewtab-n"><?= count($v['images']) ?></span>
                            <?php endif; ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- ================= info panel ================= -->
            <aside class="pdp-info">
                <span class="pdp-eyebrow"><?= e(trans('common.factory_direct')) ?></span>
                <h1 class="pdp-title"><?= e($product['name'] ?? '') ?></h1>

                <div class="pdp-meta-row">
                    <span class="pdp-code-chip"><?= e(trans('common.sku')) ?>: <?= e($modelCode) ?></span>
                    <?php if (!empty($product['is_featured'])): ?>
                        <span class="pdp-code-chip is-teal"><?= e(trans('common.featured')) ?></span>
                    <?php endif; ?>
                </div>

                <?php if (count($variants) > 1): ?>
                    <div class="pdp-variants" aria-label="<?= e(trans('products.variants_label')) ?>">
                        <?php foreach (array_slice($variants, 0, 4) as $variant): ?>
                            <span class="pdp-variant"><?= e((string) ($variant['variant_name'] ?? $variant['sku'] ?? '')) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="pdp-guarantee">
                    <span class="pdp-guarantee-shield" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" width="22" height="22"><path d="M12 3 20 6.2v5.3c0 4.9-3.4 8.6-8 10.5-4.6-1.9-8-5.6-8-10.5V6.2L12 3Z"/><path d="m8.8 12 2.2 2.2 4.2-4.7"/></svg>
                        <b>10Y</b>
                    </span>
                    <div>
                        <strong><?= e(trans('products.guarantee_title')) ?></strong>
                        <p><?= e(trans('products.guarantee_std')) ?></p>
                    </div>
                </div>

                <?php $desc = (string) ($product['short_description'] ?? $product['description'] ?? ''); ?>
                <?php if ($desc !== ''): ?>
                    <div class="pdp-desc">
                        <h3 class="pdp-block-title"><?= e(trans('products.description')) ?></h3>
                        <p><?= nl2br(e($desc)) ?></p>
                    </div>
                <?php endif; ?>

                <div class="pdp-specs">
                    <h3 class="pdp-block-title"><?= e(trans('products.specs_title')) ?></h3>
                    <dl class="pdp-specs-list">
                        <?php foreach ($specs as $row): ?>
                            <?php
                                $key = (string) ($row['spec_key'] ?? '');
                                $value = (string) ($row['spec_value'] ?? '');
                                if ($key === '' || $value === '') { continue; }
                                $unit = (string) ($row['unit'] ?? '');
                            ?>
                            <div class="pdp-spec-row">
                                <dt><?= e($key) ?></dt>
                                <dd><?= e($value . ($unit !== '' ? ' ' . $unit : '')) ?></dd>
                            </div>
                        <?php endforeach; ?>
                        <?php if ($hasDims): ?>
                            <div class="pdp-spec-row">
                                <dt><?= e(trans('products.dimensions')) ?></dt>
                                <dd><?= e($fmt($dims['w'])) ?> &times; <?= e($fmt($dims['h'])) ?> &times; <?= e($fmt($dims['d'])) ?> mm</dd>
                            </div>
                        <?php endif; ?>
                    </dl>
                    <p class="pdp-spec-note">
                        <svg viewBox="0 0 20 20" fill="currentColor" width="13" height="13" aria-hidden="true"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                        <?= e(trans('products.spec_note')) ?>
                    </p>
                </div>

                <div class="pdp-actions">
                    <a href="<?= e(route('contact')) ?>" class="pdp-cta">
                        <span><?= e(trans('products.request_quote')) ?></span>
                        <svg viewBox="0 0 20 20" fill="currentColor" width="15" height="15" aria-hidden="true"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                    </a>
                    <a href="<?= e(route('products.index')) ?>" class="pdp-back">&larr; <?= e(trans('products.title')) ?></a>
                </div>
                <p class="pdp-quote-note"><?= e(trans('products.quote_note')) ?></p>
            </aside>
        </div>
    </div>
</main>
