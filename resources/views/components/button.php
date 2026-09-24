<?php
$label = $props['label'] ?? '';
$variant = $props['variant'] ?? 'primary';
$type = $props['type'] ?? 'button';
$size = $props['size'] ?? 'md';
$href = $props['href'] ?? null;
$classes = trim('btn btn-' . $variant . ($size !== 'md' ? ' btn-' . $size : '') . ' ' . ($props['class'] ?? ''));
?>
<?php if ($href !== null): ?>
<a href="<?= e($href) ?>" class="<?= e($classes) ?>"><?= e($label) ?><?= $slot ?? '' ?></a>
<?php else: ?>
<button type="<?= e($type) ?>" class="<?= e($classes) ?>"><?= e($label) ?><?= $slot ?? '' ?></button>
<?php endif; ?>
