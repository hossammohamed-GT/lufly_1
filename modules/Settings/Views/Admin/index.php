<?php
$view->layout('layouts.admin');
?>
<form method="post" action="<?= e(route('admin.settings.update')) ?>" class="stack admin-form">
    <?= csrf_field() ?>
    <?= $view->component('input', ['name' => 'company_name', 'label' => 'Company name', 'value' => $settings['company_name'] ?? '', 'required' => true]) ?>
    <?= $view->component('input', ['name' => 'contact_email', 'label' => trans('common.email'), 'type' => 'email', 'value' => $settings['contact_email'] ?? '', 'required' => true]) ?>
    <?= $view->component('input', ['name' => 'contact_phone', 'label' => trans('common.phone'), 'value' => $settings['contact_phone'] ?? '']) ?>
    <?= $view->component('input', ['name' => 'default_meta_description', 'label' => 'Default meta description', 'value' => $settings['default_meta_description'] ?? '']) ?>
    <?= $view->component('button', ['label' => trans('common.save'), 'variant' => 'primary', 'type' => 'submit']) ?>
</form>
