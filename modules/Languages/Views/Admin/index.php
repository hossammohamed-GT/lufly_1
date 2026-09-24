<?php
$view->layout('layouts.admin');
?>
<table class="table">
    <thead>
        <tr>
            <th><?= e(trans('common.name')) ?></th><th>Code</th><th>Native</th>
            <th><?= e(trans('common.status')) ?></th><th><?= e(trans('common.actions')) ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($languages as $language): ?>
            <tr>
                <td><?= e($language->name) ?></td>
                <td><?= e($language->code) ?></td>
                <td><?= e($language->native_name) ?></td>
                <td><span class="badge badge-<?= $language->active ? 'active' : 'inactive' ?>"><?= $language->active ? e(trans('common.active')) : e(trans('common.inactive')) ?></span></td>
                <td>
                    <form method="post" action="<?= e(route('admin.languages.toggle', ['id' => $language->id])) ?>" class="inline-form">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-secondary"><?= e($language->active ? trans('common.inactive') : trans('common.active')) ?></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<h2 class="section-title"><?= e(trans('common.create')) ?></h2>
<form method="post" action="<?= e(route('admin.languages.store')) ?>" class="stack admin-form">
    <?= csrf_field() ?>
    <div class="grid grid-3">
        <?= $view->component('input', ['name' => 'code', 'label' => 'Code', 'placeholder' => 'fr', 'required' => true]) ?>
        <?= $view->component('input', ['name' => 'name', 'label' => trans('common.name'), 'placeholder' => 'French', 'required' => true]) ?>
        <?= $view->component('input', ['name' => 'native_name', 'label' => 'Native name', 'placeholder' => 'Français', 'required' => true]) ?>
    </div>
    <div class="grid grid-3">
        <div class="field">
            <label class="field-label" for="dir">Direction</label>
            <select class="input" id="dir" name="dir"><option value="ltr">LTR</option><option value="rtl">RTL</option></select>
        </div>
        <?= $view->component('input', ['name' => 'sort_order', 'label' => 'Sort', 'type' => 'number', 'value' => '0']) ?>
        <div class="field">
            <label class="field-label" for="active"><?= e(trans('common.status')) ?></label>
            <select class="input" id="active" name="active"><option value="1"><?= e(trans('common.active')) ?></option><option value="0"><?= e(trans('common.inactive')) ?></option></select>
        </div>
    </div>
    <?= $view->component('button', ['label' => trans('common.save'), 'variant' => 'primary', 'type' => 'submit']) ?>
</form>
