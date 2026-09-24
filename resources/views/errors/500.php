<?php
$view->layout('layouts.frontend');
$view->pushStyle('frontend/errors/errors.css');
?>
<section class="container error-page">
    <p class="error-code"><?= (int) ($status ?? 500) ?></p>
    <h1 class="error-title"><?= e($title ?? 'Server Error') ?></h1>
    <p class="error-message"><?= e($message ?? '') ?></p>
    <?php if (!empty($debug)): ?>
        <details class="error-debug">
            <summary><?= e($debug['exception'] ?? 'Exception') ?></summary>
            <p><?= e(($debug['file'] ?? '') . ':' . ($debug['line'] ?? '')) ?></p>
            <pre><?php foreach ((array) ($debug['trace'] ?? []) as $line): ?><?= e($line) . "\n" ?><?php endforeach; ?></pre>
        </details>
    <?php endif; ?>
</section>
