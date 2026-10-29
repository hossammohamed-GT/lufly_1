<?php
/** @var Core\View\View $view */
$view->layout('layouts.admin');
?>
<form method="post" action="<?= e(route('admin.password.update')) ?>" class="stack admin-form">
    <?= csrf_field() ?>
    <?= $view->component('input', ['name' => 'current_password', 'label' => trans('auth.current_password'), 'type' => 'password', 'required' => true]) ?>
    <?= $view->component('input', ['name' => 'password', 'label' => trans('auth.new_password'), 'type' => 'password', 'required' => true]) ?>
    <?= $view->component('input', ['name' => 'password_confirmation', 'label' => trans('common.password_confirmation'), 'type' => 'password', 'required' => true]) ?>
    <p class="admin-hint"><?= e(trans('auth.password_hint')) ?></p>
    <div class="form-actions">
        <?= $view->component('button', ['label' => trans('common.change_password'), 'variant' => 'primary', 'type' => 'submit']) ?>
        <?= $view->component('button', ['label' => trans('common.cancel'), 'variant' => 'ghost', 'href' => route('admin.dashboard')]) ?>
    </div>
</form>
