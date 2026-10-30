<?php
/** @var Core\View\View $view */
$view->layout('layouts.admin');
/** @var array<int, array<string, mixed>> $collections */
/** @var string $locale */
?>
<div data-generic-page data-quick-url="<?= e(route('admin.collections.quick')) ?>">
    <div class="admin-toolbar adm-filters">
        <span class="adm-quick-add">
            <input class="input" type="text" data-quick-name maxlength="255"
                   placeholder="<?= e(trans('categories.quick_name_placeholder')) ?>"
                   aria-label="<?= e(trans('categories.quick_name_placeholder')) ?>">
            <button type="button" class="btn btn-primary" data-quick-add>+ <?= e(trans('categories.collection_create')) ?></button>
        </span>
    </div>

    <p class="admin-hint"><?= e(trans('categories.inline_hint')) ?></p>

    <div class="cat-tree" data-generic-list>
        <?php if ($collections === []): ?>
            <p class="media-empty"><?= e(trans('common.no_results')) ?></p>
        <?php endif; ?>
        <?php foreach ($collections as $collection): ?>
            <?= $view->render('admin.collections._row', ['collection' => $collection, 'locale' => $locale]) ?>
        <?php endforeach; ?>
    </div>
</div>
