<?php
/** @var Core\View\View $view */
$view->pushStyle('frontend/css/product-card.css');
$view->pushStyle('frontend/home/masterpieces/masterpieces.css');
/** @var array $featuredProducts */
$fallbackImg = '/images/products/prod_146_1620-111-a.jpg';
$featured = is_array($featuredProducts ?? null) ? $featuredProducts : [];
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

        <div class="masterpieces-grid catalog-grid">
            <?php foreach (array_slice($featured, 0, 4) as $idx => $product):
                $img = (string) ($product['image'] ?? '') !== '' ? (string) $product['image'] : $fallbackImg;
                $situ = (string) ($product['situ_image'] ?? '');
                $name = (string) ($product['name'] ?? ('LUFLY ' . ($product['sku'] ?? 'Sanitary Fixture')));
                $sku = (string) ($product['sku'] ?? 'SKU-PENDING');
                $desc = trim((string) ($product['short_description'] ?? ''));
                $slug = (string) ($product['slug'] ?? ($product['id'] ?? ''));
                $detailUrl = $slug !== '' ? route('products.show', ['slug' => $slug]) : route('products.index');
            ?>
                <article class="pcard" style="--pcard-delay: <?= e((string) (0.06 * ($idx + 1))) ?>s">
                    <a class="pcard-media" href="<?= e($detailUrl) ?>">
                        <img src="<?= e(asset($img)) ?>" alt="<?= e($name) ?>"
                             class="pcard-img" loading="lazy" decoding="async" width="420" height="320"
                             onerror="this.onerror=null; this.src='<?= e(asset($fallbackImg)) ?>';">
                        <?php if ($situ !== ''): ?>
                            <img src="<?= e(asset($situ)) ?>" alt=""
                                 class="pcard-img pcard-img-situ" loading="lazy" decoding="async" width="420" height="320"
                                 onerror="this.onerror=null; this.remove();">
                        <?php endif; ?>
                        <span class="pcard-hint" aria-hidden="true">
                            <svg viewBox="0 0 20 20" fill="currentColor" width="13" height="13"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                            <?= e(trans('products.view_details')) ?>
                        </span>
                    </a>
                    <div class="pcard-body">
                        <div class="pcard-top">
                            <span class="pcard-code"><?= e($sku) ?></span>
                            <span class="pcard-no"><?= str_pad((string) ($idx + 1), 3, '0', STR_PAD_LEFT) ?></span>
                        </div>
                        <h3 class="pcard-title">
                            <a href="<?= e($detailUrl) ?>"><?= e($name) ?></a>
                        </h3>
                        <?php if ($desc !== ''): ?>
                            <p class="pcard-desc"><?= e($desc) ?></p>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
