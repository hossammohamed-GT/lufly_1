<?php
$view->layout('layouts.admin');
$keywords = is_array($seo['keywords'] ?? null) ? implode(', ', $seo['keywords']) : (string) ($seo['keywords'] ?? '');
?>
<form method="post" action="<?= e(route('admin.seo.update')) ?>" class="stack admin-form">
    <?= csrf_field() ?>
    <?= $view->component('input', ['name' => 'title', 'label' => 'Meta title', 'value' => $seo['title'] ?? '', 'required' => true]) ?>
    <?= $view->component('input', ['name' => 'description', 'label' => 'Meta description', 'value' => $seo['description'] ?? '', 'required' => true]) ?>
    <?= $view->component('input', ['name' => 'keywords', 'label' => 'Keywords (comma separated)', 'value' => $keywords]) ?>
    <?= $view->component('button', ['label' => trans('common.save'), 'variant' => 'primary', 'type' => 'submit']) ?>
</form>
