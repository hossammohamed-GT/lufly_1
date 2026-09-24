<?php
$view->layout('layouts.admin');
?>
<div class="admin-toolbar">
    <span></span>
    <?= $view->component('button', ['label' => trans('common.create_user'), 'variant' => 'primary', 'href' => route('admin.users.create')]) ?>
</div>

<table class="table">
    <thead>
        <tr>
            <th>ID</th><th><?= e(trans('common.name')) ?></th><th><?= e(trans('common.email')) ?></th>
            <th><?= e(trans('common.status')) ?></th><th><?= e(trans('common.actions')) ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($paginator->items() as $user): ?>
            <tr>
                <td><?= (int) $user->id ?></td>
                <td><?= e($user->name) ?></td>
                <td><?= e($user->email) ?></td>
                <td><span class="badge badge-<?= e($user->status) ?>"><?= e(trans('common.' . $user->status)) ?></span></td>
                <td class="row-actions">
                    <a class="btn btn-sm btn-secondary" href="<?= e(route('admin.users.edit', ['id' => $user->id])) ?>"><?= e(trans('common.edit')) ?></a>
                    <form method="post" action="<?= e(route('admin.users.destroy', ['id' => $user->id])) ?>" class="inline-form"
                          onsubmit="return confirm('OK?');">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-danger"><?= e(trans('common.delete')) ?></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
