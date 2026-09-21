<?php
/** @var Core\View\View $view */
$view->layout('layouts.admin');
/** @var Core\Database\Paginator $paginator */
$locale = $translator->getLocale();
$names = [];
foreach ($paginator->items() as $product) {
    $names[(int) $product->id] = $product->translate($locale)['name'] ?? '';
}
?>
<div class="admin-toolbar">
    <form method="get" class="inline-form">
        <input class="input" type="search" name="q" value="<?= e(request()->query('q', '')) ?>" placeholder="<?= e(trans('common.search')) ?>">
        <?= $view->component('button', ['label' => trans('common.search'), 'variant' => 'secondary', 'type' => 'submit']) ?>
    </form>
    <?= $view->component('button', ['label' => trans('common.create_product'), 'variant' => 'primary', 'href' => route('admin.products.create')]) ?>
</div>

<table class="table">
    <thead>
        <tr>
            <th>ID</th><th><?= e(trans('common.model_code')) ?></th><th><?= e(trans('common.name')) ?></th><th><?= e(trans('common.slug')) ?></th>
            <th><?= e(trans('common.price')) ?></th><th><?= e(trans('common.status')) ?></th><th><?= e(trans('common.actions')) ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($paginator->items() as $product): ?>
            <?php $data = $product->translate($locale); ?>
            <tr>
                <td><?= (int) $product->id ?></td>
                <td><?= e((string) ($product->model_code ?? '')) ?></td>
                <td><?= e((string) ($names[(int) $product->id] ?? '')) ?></td>
                <td><?= e((string) $product->slug) ?></td>
                <td><?= e((string) ($data['price'] ?? 0)) ?></td>
                <td><span class="badge badge-<?= e((string) $product->status) ?>"><?= e(trans('common.' . ((string) $product->status === 'active' ? 'active' : 'draft'))) ?></span></td>
                <td class="row-actions">
                    <a class="btn btn-sm btn-secondary" href="<?= e(route('admin.products.edit', ['id' => $product->id])) ?>"><?= e(trans('common.edit')) ?></a>
                    <form method="post" action="<?= e(route('admin.products.destroy', ['id' => $product->id])) ?>" class="inline-form"
                          onsubmit="return confirm('OK?');">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-danger"><?= e(trans('common.delete')) ?></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?= $view->component('pagination', ['paginator' => $paginator]) ?>
