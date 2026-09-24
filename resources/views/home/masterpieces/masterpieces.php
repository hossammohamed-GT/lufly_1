<?php
$view->pushStyle('frontend/css/product-card.css');
$view->pushStyle('frontend/home/masterpieces/masterpieces.css');
$view->pushScript('frontend/products/catalog/catalog.js');
$view->pushStyle('frontend/components/skeleton/skeleton.css');
$view->pushScript('frontend/components/skeleton/skeleton.js');
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
            <?php foreach (array_slice($featured, 0, 4) as $idx => $product):
                $img = (string) ($product['image'] ?? '') !== '' ? (string) $product['image'] : $fallbackImg;
                $cardSlides = [];
                foreach ((array) ($product['card_slides'] ?? []) as $slide) {
                    $slidePath = (string) ($slide['path'] ?? '');
                    if ($slidePath !== '') {
                        $cardSlides[] = ['path' => $slidePath, 'kind' => (string) ($slide['kind'] ?? 'photo')];
                    }
                }
                if ($cardSlides === []) {
                    $cardSlides = [['path' => $img, 'kind' => 'photo']];
                }
                $cardKinds = ['drawing' => trans('products.view_drawing'), 'situ' => trans('products.view_situ')];
                $name = (string) ($product['name'] ?? ('LUFLY ' . ($product['sku'] ?? 'Sanitary Fixture')));
                $sku = (string) ($product['sku'] ?? 'SKU-PENDING');
                $desc = trim((string) ($product['short_description'] ?? ''));
                $slug = (string) ($product['slug'] ?? ($product['id'] ?? ''));
                $detailUrl = $slug !== '' ? route('products.show', ['slug' => $slug]) : route('products.index');
            ?>
                <article class="pcard" style="--pcard-delay: <?= e((string) (0.06 * ($idx + 1))) ?>s">
                    <a class="pcard-media" href="<?= e($detailUrl) ?>"
                       <?= count($cardSlides) > 1 ? 'data-pcard-cycle data-pcard-kind-drawing="' . e($cardKinds['drawing']) . '" data-pcard-kind-situ="' . e($cardKinds['situ']) . '"' : '' ?>>
                        <?php foreach ($cardSlides as $ci => $slide): ?>
                            <?php $slideKind = (string) $slide['kind']; ?>
                            <img src="<?= e(asset($slide['path'])) ?>"
                                 alt="<?= $ci === 0 ? e($name) : ($slideKind === 'photo' ? '' : e($cardKinds[$slideKind] ?? '') . ' — ' . e($name)) ?>"
                                 class="pcard-img<?= $ci === 0 ? ' is-on' : '' ?>"
                                 data-pcard-slide="<?= (int) $ci ?>"
                                 data-pcard-kind="<?= e($slideKind) ?>"
                                 loading="lazy" decoding="async" width="420" height="320"
                                 onerror="this.onerror=null; this.remove();">
                        <?php endforeach; ?>
                        <?php if (count($cardSlides) > 1): ?>
                            <span class="pcard-dots" aria-hidden="true">
                                <?php foreach ($cardSlides as $ci => $unusedDot): ?>
                                    <i class="pcard-dot<?= $ci === 0 ? ' is-on' : '' ?>" data-pcard-dot="<?= (int) $ci ?>"></i>
                                <?php endforeach; ?>
                            </span>
                            <span class="pcard-kind" data-pcard-kind-label aria-hidden="true" hidden></span>
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
    </div>
</section>
