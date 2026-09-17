<?php
/** @var Core\View\View $view */
$view->layout('layouts.frontend');
$view->pushStyle('frontend/errors/errors.css');
?>
<section class="container error-page">
    <p class="error-code">422</p>
    <h1 class="error-title"><?= e($title ?? 'Unprocessable Entity') ?></h1>
    <p class="error-message"><?= e($message ?? '') ?></p>
    <?php if (!empty($errors)): ?>
        <ul class="error-list">
            <?php foreach ($errors as $messages): ?>
                <?php foreach ((array) $messages as $m): ?><li><?= e($m) ?></li><?php endforeach; ?>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <?= $view->component('button', ['label' => trans('common.back'), 'variant' => 'primary', 'href' => route('home')]) ?>
</section>
