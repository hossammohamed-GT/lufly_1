<?php
/** @var Core\View\View $view */
$view->pushStyle('frontend/css/product-card.css');
$view->pushStyle('frontend/home/masterpieces/masterpieces.css');
$view->pushScript('frontend/products/catalog/catalog.js');
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
                /* same media contract as the catalogue grid: every gallery and
                   in-situ photo, drawings excluded, de-duplicated */
                $cardImgs = is_array($product['gallery'] ?? null) ? array_values($product['gallery']) : [];
                foreach ((is_array($product['situ_images'] ?? null) ? $product['situ_images'] : []) as $s) {
                    $cardImgs[] = $s;
                }
                $cardImgs = array_values(array_unique(array_filter($cardImgs, static fn ($v) => (string) $v !== '')));
                if ($cardImgs === []) {
                    $cardImgs = [$img];
                }
                $name = (string) ($product['name'] ?? ('LUFLY ' . ($product['sku'] ?? 'Sanitary Fixture')));
                $sku = (string) ($product['sku'] ?? 'SKU-PENDING');
                $desc = trim((string) ($product['short_description'] ?? ''));
                $slug = (string) ($product['slug'] ?? ($product['id'] ?? ''));
                $detailUrl = $slug !== '' ? route('products.show', ['slug' => $slug]) : route('products.index');
            ?>
                <article class="pcard" style="--pcard-delay: <?= e((string) (0.06 * ($idx + 1))) ?>s">
                    <a class="pcard-media" href="<?= e($detailUrl) ?>"
                       <?= count($cardImgs) > 1 ? 'data-pcard-cycle' : '' ?>>
                        <?php foreach ($cardImgs as $ci => $cSrc): ?>
                            <img src="<?= e(asset($cSrc)) ?>"
                                 alt="<?= $ci === 0 ? e($name) : '' ?>"
                                 class="pcard-img<?= $ci === 0 ? ' is-on' : '' ?>"
                                 data-pcard-slide="<?= (int) $ci ?>"
                                 loading="lazy" decoding="async" width="420" height="320"
                                 onerror="this.onerror=null; this.remove();">
                        <?php endforeach; ?>
                        <?php if (count($cardImgs) > 1): ?>
                            <span class="pcard-dots" aria-hidden="true">
                                <?php foreach ($cardImgs as $ci => $unusedDot): ?>
                                    <i class="pcard-dot<?= $ci === 0 ? ' is-on' : '' ?>" data-pcard-dot="<?= (int) $ci ?>"></i>
                                <?php endforeach; ?>
                            </span>
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
