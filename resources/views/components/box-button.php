<?php
/**
 * Put-this-piece-in-the-box toggle — the heart's sibling.
 *
 * The catalogue also uses the cart variant as a price-gated action. With no
 * positive price it renders a genuinely disabled button without data-box-add,
 * so neither pointer nor delegated JavaScript handlers can add the product.
 * Once a price exists the existing box endpoint and shared state take over.
 *
 * @var Core\View\View $view
 * @var array{product_id?: int|string, name?: string, variant?: string, on?: bool, label?: string, disabled?: bool, disabled_label?: string} $props
 */
if (!feature('box', true) || !(bool) config('box.enabled', true)) {
    return;
}

$productId = (int) ($props['product_id'] ?? 0);
if ($productId <= 0) {
    return;
}

$variant = (string) ($props['variant'] ?? 'card');
$disabled = (bool) ($props['disabled'] ?? false);
$on = !$disabled && (bool) ($props['on'] ?? false);
$name = trim((string) ($props['name'] ?? ''));
$label = $disabled
    ? (string) ($props['disabled_label'] ?? $props['label'] ?? trans('products.cart_pending'))
    : ($on ? trans('box.added') : (string) ($props['label'] ?? trans('box.add')));
?>
<button type="button"
        class="boxbtn boxbtn-<?= e($variant) ?><?= $on ? ' is-on' : '' ?>"
        data-box-product="<?= $productId ?>"
        <?php if (!$disabled): ?>
        data-box-add
        data-box-endpoint="<?= e(route('box.add')) ?>"
        data-box-label-on="<?= e(trans('box.added')) ?>"
        data-box-label-off="<?= e((string) ($props['label'] ?? trans('box.add'))) ?>"
        data-box-error="<?= e(trans('box.err')) ?>"
        <?php endif; ?>
        aria-pressed="<?= $on ? 'true' : 'false' ?>"
        aria-label="<?= e($label) ?><?= $name !== '' ? ' — ' . e($name) : '' ?>"
        title="<?= e($label) ?>"
        <?= $disabled ? 'disabled aria-disabled="true"' : '' ?>>
    <span class="boxbtn-icon" aria-hidden="true">
        <?php if ($variant === 'cart'): ?>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="9" cy="20" r="1.3"/><circle cx="18" cy="20" r="1.3"/>
                <path d="M3 4h2l2.2 10.2a2 2 0 0 0 2 1.6h8.9a2 2 0 0 0 2-1.6L21.5 8H6"/>
            </svg>
        <?php else: ?>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 8.5 6 4h12l2 4.5"/>
                <path d="M4 8.5h16V19a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V8.5Z"/>
                <path d="M9.5 13h5"/>
            </svg>
        <?php endif; ?>
    </span>
    <?php if (!in_array($variant, ['card', 'cart'], true)): ?>
        <span class="boxbtn-label" data-box-label><?= e($label) ?></span>
    <?php endif; ?>
</button>
