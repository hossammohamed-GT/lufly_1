<?php
/**
 * Premium catalogue card. All commercial and technical content comes from the
 * translated product payload; ProductCardPresenter only summarizes real data.
 *
 * @var Core\View\View $view
 * @var array{product?: array<string, mixed>, slug?: string, index?: int, heading?: string, saved?: bool, in_box?: bool} $props
 */
$item = is_array($props['product'] ?? null) ? $props['product'] : [];
$card = is_array($item['card'] ?? null)
    ? $item['card']
    : \Modules\Products\Services\ProductCardPresenter::make($item, isset($item['locale']) ? (string) $item['locale'] : null);
$productId = (int) ($item['id'] ?? 0);
$slug = (string) ($props['slug'] ?? $item['slug'] ?? '');
$cardUrl = route('products.show', ['slug' => $slug]);
$cardCode = trim((string) ($item['sku'] ?? ''));
if ($cardCode === '') {
    $cardCode = trim((string) ($item['model_code'] ?? ''));
}
$cardName = trim((string) ($item['name'] ?? ''));
if ($cardName === '') {
    $cardName = trans('products.name_pending');
}
$shortDesc = trim((string) ($item['short_description'] ?? ''));
if ($shortDesc === '') {
    $shortDesc = trim((string) ($item['description'] ?? ''));
}
/* The structured summary below already carries the verified dimensions;
   keep the editorial excerpt concise instead of repeating the whole block. */
foreach ([
    'Verified drawing dimensions:',
    'Doğrulanmış teknik çizim ölçüleri:',
    'Ověřené rozměry z technického výkresu:',
] as $dimMarker) {
    $verifiedBlock = strpos($shortDesc, $dimMarker);
    if ($verifiedBlock !== false) {
        $shortDesc = trim(substr($shortDesc, 0, $verifiedBlock));
    }
}
$heading = ($props['heading'] ?? 'h2') === 'h3' ? 'h3' : 'h2';
$index = max(0, (int) ($props['index'] ?? 0));
$fallbackImg = '/images/favicon.png';
$img = trim((string) ($item['image'] ?? '')) !== '' ? (string) $item['image'] : $fallbackImg;

$cardSlides = [];
foreach ((array) ($item['card_slides'] ?? []) as $slide) {
    $slidePath = trim((string) ($slide['path'] ?? ''));
    if ($slidePath !== '') {
        $cardSlides[] = ['path' => $slidePath, 'kind' => (string) ($slide['kind'] ?? 'photo')];
    }
}
if ($cardSlides === []) {
    $cardSlides = [['path' => $img, 'kind' => 'photo']];
}
$cardKinds = ['drawing' => trans('products.view_drawing'), 'situ' => trans('products.view_situ')];
$isFeatured = !empty($item['is_featured']) || !empty($card['is_featured']);
$hasPrice = !empty($card['has_price']);
$hasFeaturePanel = $card['badge'] !== '' || $card['features'] !== [];
$colorVariants = is_array($item['color_variants'] ?? null) ? $item['color_variants'] : [];
?>
<article class="pcard<?= $isFeatured ? ' is-featured' : '' ?><?= $hasPrice ? ' has-price' : ' is-price-pending' ?>"
         data-product-card data-product-id="<?= $productId ?>"<?= $isFeatured ? ' data-featured="1"' : '' ?>
         style="--pcard-delay: <?= e((string) (0.04 * (($index % 6) + 1))) ?>s">
    <div class="pcard-stage">
        <a class="pcard-media" href="<?= e($cardUrl) ?>"
           <?= count($cardSlides) > 1 ? 'data-pcard-cycle data-pcard-kind-drawing="' . e($cardKinds['drawing']) . '" data-pcard-kind-situ="' . e($cardKinds['situ']) . '"' : '' ?>>
            <?php foreach ($cardSlides as $ci => $slide): ?>
                <?php $slideKind = (string) $slide['kind']; ?>
                <img src="<?= e(asset($slide['path'])) ?>"
                     alt="<?= $ci === 0 ? e($cardName) : ($slideKind === 'photo' ? '' : e($cardKinds[$slideKind] ?? '') . ' — ' . e($cardName)) ?>"
                     class="pcard-img<?= $ci === 0 ? ' is-on' : '' ?>"
                     data-pcard-slide="<?= (int) $ci ?>"
                     data-pcard-kind="<?= e($slideKind) ?>"
                     loading="lazy" decoding="async" width="420" height="320"
                     onerror="this.onerror=null; this.remove();">
            <?php endforeach; ?>
        </a>
        <?php if (count($cardSlides) > 1): ?>
            <div class="pcard-media-meta">
                <span class="pcard-dots" aria-hidden="true">
                    <?php foreach ($cardSlides as $ci => $unusedDot): ?>
                        <i class="pcard-dot<?= $ci === 0 ? ' is-on' : '' ?>" data-pcard-dot="<?= (int) $ci ?>"></i>
                    <?php endforeach; ?>
                </span>
                <span class="pcard-kind" data-pcard-kind-label aria-hidden="true" hidden></span>
            </div>
        <?php endif; ?>
    </div>

    <div class="pcard-body">
        <div class="pcard-top pcard-heading">
            <<?= $heading ?> class="pcard-title">
                <a href="<?= e($cardUrl) ?>"><?= e($cardName) ?></a>
            </<?= $heading ?>>
            <?= $view->component('favorite-button', [
                'product_id' => $productId,
                'name' => $cardName,
                'variant' => 'card',
                'on' => (bool) ($props['saved'] ?? false),
            ]) ?>
        </div>
        <?php if ($cardCode !== ''): ?>
            <span class="pcard-code"><?= e($cardCode) ?></span>
        <?php endif; ?>
        <?php if ($shortDesc !== ''): ?>
            <p class="pcard-desc"><?= e($shortDesc) ?></p>
        <?php endif; ?>

        <?php if ($hasFeaturePanel): ?>
            <div class="pcard-feature-panel">
                <?php if ($card['badge'] !== ''): ?>
                    <span class="pcard-badge<?= $isFeatured ? ' is-featured-badge' : '' ?>">
                        <?php if ($isFeatured): ?>
                            <svg class="pcard-badge-star" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" focusable="false">
                                <path d="M8 1.25l1.72 4.31 4.63.42-3.5 3.06 1.04 4.54L8 11.18l-3.89 2.4 1.04-4.54-3.5-3.06 4.63-.42L8 1.25z"/>
                            </svg>
                        <?php endif; ?>
                        <span><?= e($card['badge']) ?></span>
                    </span>
                <?php endif; ?>
                <?php if ($card['features'] !== []): ?>
                    <span class="pcard-features" role="list">
                        <?php foreach ($card['features'] as $feature): ?>
                            <span class="pcard-feature" role="listitem">
                                <?= $view->component('product-card-icon', ['name' => $feature['icon']]) ?>
                                <span><?= e($feature['label']) ?></span>
                            </span>
                        <?php endforeach; ?>
                    </span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($card['finishes'] !== [] || count($colorVariants) > 1): ?>
            <?= $view->component('product-color-variants', ['item' => $item, 'card' => $card]) ?>
        <?php endif; ?>

        <?php if ($card['specs'] !== []): ?>
            <?php $specCount = count($card['specs']); ?>
            <div class="pcard-specs<?= ($specCount % 2 === 1) ? ' pcard-specs--odd' : '' ?>" role="list" data-spec-count="<?= $specCount ?>">
                <?php foreach ($card['specs'] as $specIndex => $spec): ?>
                    <div class="pcard-spec<?= ($specCount % 2 === 1 && $specIndex === 0) ? ' pcard-spec--wide' : '' ?>" role="listitem">
                        <?= $view->component('product-card-icon', ['name' => $spec['icon']]) ?>
                        <div>
                            <span class="pcard-spec-label"><?= e($spec['label']) ?></span>
                            <span class="pcard-spec-value"><?= e($spec['value']) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="pcard-actions">
            <div class="pcard-action-copy">
                <span class="pcard-price">
                    <small><?= e($card['price_caption']) ?></small>
                    <strong<?= $hasPrice ? '' : ' class="is-pending"' ?>><?= e($card['price_label']) ?></strong>
                </span>
                <a class="pcard-details" href="<?= e($cardUrl) ?>">
                    <?= e(trans('products.view_details')) ?>
                    <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M4 10h12m-5-5 5 5-5 5"/></svg>
                </a>
            </div>
            <?= $view->component('box-button', [
                'product_id' => $productId,
                'name' => $cardName,
                'variant' => 'cart',
                'on' => (bool) ($props['in_box'] ?? false),
                'label' => trans('products.add_to_cart'),
                'disabled' => empty($card['cart_enabled']),
                'disabled_label' => $card['cart_label'],
            ]) ?>
        </div>
    </div>
</article>
