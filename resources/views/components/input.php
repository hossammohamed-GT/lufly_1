<?php
$name = $props['name'] ?? '';
$label = $props['label'] ?? '';
$type = $props['type'] ?? 'text';
$value = $props['value'] ?? old($name, '');
$placeholder = $props['placeholder'] ?? '';
$required = !empty($props['required']);
$error = $props['error'] ?? null;
?>
<div class="field">
    <?php if ($label !== ''): ?>
        <label class="field-label" for="input-<?= e($name) ?>"><?= e($label) ?><?= $required ? ' *' : '' ?></label>
    <?php endif; ?>
    <input class="input<?= $error ? ' is-invalid' : '' ?>"
           id="input-<?= e($name) ?>"
           type="<?= e($type) ?>"
           name="<?= e($name) ?>"
           value="<?= e($value) ?>"
           placeholder="<?= e($placeholder) ?>"
           <?= $required ? 'required' : '' ?>>
    <?php if ($error): ?><p class="field-error"><?= e($error) ?></p><?php endif; ?>
</div>
