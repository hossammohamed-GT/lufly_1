<?php
/**
 * Separates the selected product's own (possibly two-tone) finish from the
 * independently clickable colours available across its reviewed family.
 * Every option remains a real product link for the no-JavaScript fallback.
 *
 * @var array{item?: array<string, mixed>, card?: array<string, mixed>} $props
 */
$item = is_array($props['item'] ?? null) ? $props['item'] : [];
$currentCard = is_array($props['card'] ?? null)
    ? $props['card']
    : \Modules\Products\Services\ProductCardPresenter::make($item);
$variants = is_array($item['color_variants'] ?? null) ? $item['color_variants'] : [];
$currentId = (int) ($item['id'] ?? 0);
$hasVariants = count($variants) > 1;
$currentFinishes = is_array($currentCard['finishes'] ?? null) ? $currentCard['finishes'] : [];
$selectedVariant = null;

foreach ($variants as $variant) {
    if ((int) ($variant['id'] ?? 0) === $currentId) {
        $selectedVariant = $variant;
        break;
    }
}
if ($currentFinishes === [] && is_array($selectedVariant['card']['finishes'] ?? null)) {
    $currentFinishes = $selectedVariant['card']['finishes'];
}
if ($currentFinishes === [] && is_array($selectedVariant['finish'] ?? null)) {
    $currentFinishes = [$selectedVariant['finish']];
}

$finishLabel = static function (array $finishes): string {
    $labels = [];
    foreach ($finishes as $finish) {
        $label = trim((string) ($finish['label'] ?? ''));
        if ($label !== '' && !in_array($label, $labels, true)) {
            $labels[] = $label;
        }
    }

    return implode(' / ', $labels);
};
$toneParts = static function (array $finishes): string {
    $html = '';
    foreach (array_slice($finishes, 0, 4) as $finish) {
        $key = strtolower((string) ($finish['key'] ?? 'neutral'));
        $key = preg_replace('/[^a-z-]/', '', $key) ?: 'neutral';
        $html .= '<i class="pcard-tone pcard-swatch--' . e($key) . '" aria-hidden="true"></i>';
    }

    return $html;
};
$currentLabel = $finishLabel($currentFinishes);
if ($currentLabel === '' && is_array($selectedVariant['finish'] ?? null)) {
    $currentLabel = trim((string) ($selectedVariant['finish']['label'] ?? ''));
}
?>
<?php if ($currentFinishes !== [] || $hasVariants): ?>
    <div class="pcard-finishes pcard-finish-box" data-pcard-colors
         data-product-finish-label="<?= e(trans('products.product_finish')) ?>"
         data-color-options-label="<?= e(trans('products.available_colors')) ?>">
        <?php if ($currentFinishes !== []): ?>
            <div class="pcard-finish-piece" data-pcard-piece>
                <span class="pcard-finish-caption"><?= e(trans('products.product_finish')) ?></span>
                <span class="pcard-finish-value">
                    <span class="pcard-tone-swatch" data-pcard-piece-tones role="img"
                          aria-label="<?= e($currentLabel) ?>" title="<?= e($currentLabel) ?>"><?= $toneParts($currentFinishes) ?></span>
                    <span class="pcard-finish-count pcard-finish-current pcard-finish-name"
                          data-pcard-finish-label aria-live="polite" title="<?= e($currentLabel) ?>"><?= e($currentLabel) ?></span>
                </span>
            </div>
        <?php endif; ?>

        <?php if ($hasVariants): ?>
            <div class="pcard-color-variants pcard-finish-choices">
                <span class="pcard-finish-caption"><?= e(trans('products.available_colors')) ?></span>
                <span class="pcard-swatches" role="list" aria-label="<?= e(trans('products.available_colors')) ?>">
                    <?php foreach ($variants as $variant): ?>
                        <?php
                        $id = (int) ($variant['id'] ?? 0);
                        $finish = is_array($variant['finish'] ?? null) ? $variant['finish'] : [];
                        $label = trim((string) ($finish['label'] ?? ''));
                        $optionFinishes = !empty($variant['card']['finishes']) && is_array($variant['card']['finishes'])
                            ? $variant['card']['finishes']
                            : ($finish !== [] ? [$finish] : []);
                        $code = trim((string) ($variant['sku'] ?? ''));
                        if ($code === '') {
                            $code = trim((string) ($variant['model_code'] ?? ''));
                        }
                        $url = (string) ($variant['url'] ?? route('products.show', ['slug' => (string) ($variant['slug'] ?? '')]));
                        $selected = $id === $currentId;
                        $aria = $code !== ''
                            ? trans('products.show_color_option', ['color' => $label, 'code' => $code])
                            : $label;
                        ?>
                        <a class="pcard-swatch pcard-option<?= $selected ? ' is-active' : '' ?>"
                           role="listitem" href="<?= e($url) ?>" data-pcard-variant="<?= $id ?>"
                           title="<?= e($aria) ?>" aria-label="<?= e($aria) ?>"
                           <?= $selected ? 'aria-current="true"' : '' ?>><span class="pcard-option-tones" aria-hidden="true"><?= $toneParts($optionFinishes) ?></span></a>
                    <?php endforeach; ?>
                </span>
            </div>
        <?php endif; ?>
    </div>
    <?php if ($hasVariants): ?>
        <script type="application/json" data-pcard-variant-data><?= json_encode(
            array_values($variants),
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR,
        ) ?></script>
    <?php endif; ?>
<?php endif; ?>
