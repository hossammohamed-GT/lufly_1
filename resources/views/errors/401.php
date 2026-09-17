<?php
/** @var Core\View\View $view */
$view->layout('layouts.frontend');
$view->pushStyle('frontend/errors/errors.css');
?>
<section class="container error-page">
    <p class="error-code">401</p>
    <h1 class="error-title"><?= e($title ?? 'Unauthenticated') ?></h1>
    <p class="error-message"><?= e($message ?? '') ?></p>
    <?= $view->component('button', ['label' => trans('auth.login'), 'variant' => 'primary', 'href' => route('login')]) ?>
</section>
