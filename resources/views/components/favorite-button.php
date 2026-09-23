<?php
/**
 * Save-to-favorites toggle.
 *
 * Rendered wherever a product appears (catalogue card, product page, saved
 * list). With JavaScript it posts to the favourites endpoint and updates every
 * copy of itself for that product; the markup already carries the correct state
 * server-side, so the button is right even before the script loads.
 *
 * @var Core\View\View $view
 * @var array{product_id?: int|string, name?: string, variant?: string, on?: bool, label?: string} $props
 */
if (!feature('favorites', true)) {
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
    ? trans('favorites.saved')
    : (string) ($props['label'] ?? trans('favorites.save'));
$addedMessage = trans('favorites.added_named', ['name' => $name]);
$removedMessage = trans('favorites.removed_named', ['name' => $name]);
?>
<button type="button"
        class="favbtn favbtn-<?= e($variant) ?><?= $on ? ' is-on' : '' ?>"
        data-fav-toggle
        data-fav-product="<?= $productId ?>"
        data-fav-endpoint="<?= e(route('favorites.toggle')) ?>"
        data-fav-added="<?= e($addedMessage) ?>"
        data-fav-removed="<?= e($removedMessage) ?>"
        data-fav-label-on="<?= e(trans('favorites.saved')) ?>"
        data-fav-label-off="<?= e(trans('favorites.save')) ?>"
        aria-pressed="<?= $on ? 'true' : 'false' ?>"
        aria-label="<?= e($label) ?><?= $name !== '' ? ' — ' . e($name) : '' ?>"
        title="<?= e($label) ?>">
    <span class="favbtn-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
             stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 20.6 4.2 12.8a5.1 5.1 0 0 1 0-7.2 5.1 5.1 0 0 1 7.2 0l.6.6.6-.6a5.1 5.1 0 0 1 7.2 0 5.1 5.1 0 0 1 0 7.2Z"/>
        </svg>
        <svg class="favbtn-burst" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"
             stroke-linecap="round" aria-hidden="true">
            <circle cx="12" cy="12" r="9"/>
        </svg>
    </span>
    <?php if ($variant !== 'card'): ?>
        <span class="favbtn-label" data-fav-label><?= e($label) ?></span>
    <?php endif; ?>
</button>
