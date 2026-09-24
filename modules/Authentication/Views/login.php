<?php
$view->layout('layouts.frontend');
$view->pushStyle('frontend/auth/login/login.css');
?>
<section class="container auth-wrap">
    <div class="auth-card">
        <h1 class="auth-title"><?= e(trans('auth.sign_in')) ?></h1>
        <form method="post" action="<?= e(url('/login')) ?>" class="stack">
            <?= csrf_field() ?>
            <?= $view->component('input', ['name' => 'email', 'label' => trans('auth.email'), 'type' => 'email', 'required' => true]) ?>
            <?= $view->component('input', ['name' => 'password', 'label' => trans('auth.password'), 'type' => 'password', 'required' => true]) ?>
            <?= $view->component('button', ['label' => trans('auth.login'), 'variant' => 'primary', 'type' => 'submit']) ?>
        </form>
    </div>
</section>
