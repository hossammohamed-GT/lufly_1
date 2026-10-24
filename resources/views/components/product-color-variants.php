<?php
/**
 * Same-card colour/finish choices. Every choice is also a real product URL,
 * so it remains useful without JavaScript; the delegated script enhances it
 * into an in-place card update.
 *
 * @var array{item?: array<string, mixed>} $props
 */
$item = is_array($props['item'] ?? null) ? $props['item'] : [];
$variants = is_array($item['color_variants'] ?? null) ? $item['color_variants'] : [];
$currentId = (int) ($item['id'] ?? 0);
$currentLabel = '';
?>
<?php if (count($variants) > 1): ?>
    <div class="pcard-finishes pcard-color-variants" data-pcard-colors>
        <span class="pcard-swatches" role="list" aria-label="<?= e(trans('products.color_options')) ?>">
            <?php foreach ($variants as $variant): ?>
                <?php
                $id = (int) ($variant['id'] ?? 0);
                $finish = is_array($variant['finish'] ?? null) ? $variant['finish'] : [];
                $label = trim((string) ($finish['label'] ?? ''));
                $key = (string) ($finish['key'] ?? 'neutral');
                $code = trim((string) ($variant['sku'] ?? ''));
                if ($code === '') {
                    $code = trim((string) ($variant['model_code'] ?? ''));
                }
                $url = (string) ($variant['url'] ?? route('products.show', ['slug' => (string) ($variant['slug'] ?? '')]));
                $selected = $id === $currentId;
                if ($selected) {
                    $currentLabel = $label;
                }
                $aria = $code !== ''
                    ? trans('products.show_color_option', ['color' => $label, 'code' => $code])
                    : $label;
                ?>
                <a class="pcard-swatch pcard-swatch--<?= e($key) ?><?= $selected ? ' is-active' : '' ?>"
                   role="listitem" href="<?= e($url) ?>" data-pcard-variant="<?= $id ?>"
                   title="<?= e($aria) ?>" aria-label="<?= e($aria) ?>"
                   <?= $selected ? 'aria-current="true"' : '' ?>></a>
            <?php endforeach; ?>
        </span>
        <span class="pcard-finish-count pcard-finish-current" data-pcard-finish-label
              aria-live="polite" title="<?= e($currentLabel) ?>"><?= e($currentLabel) ?></span>
    </div>
    <script type="application/json" data-pcard-variant-data><?= json_encode(
        array_values($variants),
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
    ) ?></script>
<?php endif; ?>
