<?php
if (!feature('box', true) || !(bool) config('box.enabled', true)) {
    return;
}

$productId = (int) ($props['product_id'] ?? 0);
if ($productId <= 0) {
    return;
}

$variant = (string) ($props['variant'] ?? 'card');
$on = (bool) ($props['on'] ?? false);
$name = trim((string) ($props['name'] ?? ''));
$label = $on
    ? trans('box.added')
    : (string) ($props['label'] ?? trans('box.add'));
?>
<button type="button"
        class="boxbtn boxbtn-<?= e($variant) ?><?= $on ? ' is-on' : '' ?>"
        data-box-add
        data-box-product="<?= $productId ?>"
        data-box-endpoint="<?= e(route('box.add')) ?>"
        data-box-label-on="<?= e(trans('box.added')) ?>"
        data-box-label-off="<?= e(trans('box.add')) ?>"
        data-box-error="<?= e(trans('box.err')) ?>"
        aria-pressed="<?= $on ? 'true' : 'false' ?>"
        aria-label="<?= e($label) ?><?= $name !== '' ? ' — ' . e($name) : '' ?>"
        title="<?= e($label) ?>">
    <span class="boxbtn-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"
             stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 8.5 6 4h12l2 4.5"/>
            <path d="M4 8.5h16V19a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V8.5Z"/>
            <path d="M9.5 13h5"/>
        </svg>
    </span>
    <?php if ($variant !== 'card'): ?>
        <span class="boxbtn-label" data-box-label><?= e($label) ?></span>
    <?php endif; ?>
</button>
