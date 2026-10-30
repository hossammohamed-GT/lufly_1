<?php
/** @var Core\View\View $view */
$view->layout('layouts.admin');
/** @var array<int, array<string, mixed>> $brands */
/** @var string $locale */
?>
<div data-generic-page data-quick-url="<?= e(route('admin.brands.quick')) ?>">
    <div class="admin-toolbar adm-filters">
        <span class="adm-quick-add">
            <input class="input" type="text" data-quick-name maxlength="100"
                   placeholder="<?= e(trans('categories.quick_name_placeholder')) ?>"
                   aria-label="<?= e(trans('categories.quick_name_placeholder')) ?>">
            <button type="button" class="btn btn-primary" data-quick-add>+ <?= e(trans('categories.brand_create')) ?></button>
        </span>
    </div>

    <p class="admin-hint"><?= e(trans('categories.inline_hint')) ?></p>

    <div class="cat-tree" data-generic-list>
        <?php if ($brands === []): ?>
            <p class="media-empty"><?= e(trans('common.no_results')) ?></p>
        <?php endif; ?>
        <?php foreach ($brands as $brand): ?>
            <?= $view->render('admin.brands._row', ['brand' => $brand, 'locale' => $locale]) ?>
        <?php endforeach; ?>
    </div>
</div>
