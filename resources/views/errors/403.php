<?php
/** @var Core\View\View $view */
$view->layout('layouts.frontend');
$view->pushStyle('frontend/errors/errors.css');
?>
<section class="container error-page">
    <p class="error-code">403</p>
    <h1 class="error-title"><?= e($title ?? 'Forbidden') ?></h1>
    <p class="error-message"><?= e($message ?? '') ?></p>
    <?= $view->component('button', ['label' => trans('common.back'), 'variant' => 'primary', 'href' => route('home')]) ?>
</section>
