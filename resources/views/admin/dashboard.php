<?php
$view->layout('layouts.admin');
?>
<div class="grid grid-4 stat-grid">
    <?php foreach ($stats as $table => $count): ?>
        <div class="card stat-card">
            <div class="card-body">
                <p class="stat-value"><?= (int) $count ?></p>
                <p class="stat-label"><?= e(ucfirst(str_replace('_', ' ', $table))) ?></p>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="dash-links">
    <?= $view->component('button', ['label' => trans('common.create_product'), 'variant' => 'primary', 'href' => route('admin.products.create')]) ?>
    <?= $view->component('button', ['label' => trans('common.create_user'), 'variant' => 'secondary', 'href' => route('admin.users.create')]) ?>
    <?= $view->component('button', ['label' => trans('common.media_library'), 'variant' => 'ghost', 'href' => route('admin.media.index')]) ?>
</div>
