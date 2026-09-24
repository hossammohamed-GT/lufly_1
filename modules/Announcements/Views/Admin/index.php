<?php
$view->layout('layouts.admin');
?>
<div class="admin-toolbar">
    <p class="admin-hint"><?= e(trans('announcements.hint')) ?></p>
    <?= $view->component('button', ['label' => trans('common.create_announcement'), 'variant' => 'primary', 'href' => route('admin.announcements.create')]) ?>
</div>

<table class="table">
    <thead>
        <tr>
            <th>ID</th>
            <th><?= e(trans('announcements.message')) ?></th>
            <th><?= e(trans('common.status')) ?></th>
            <th><?= e(trans('announcements.placement')) ?></th>
            <th><?= e(trans('announcements.style')) ?></th>
            <th><?= e(trans('announcements.schedule')) ?></th>
            <th><?= e(trans('common.actions')) ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($items as $item): ?>
            <tr>
                <td><?= (int) $item['id'] ?></td>
                <td class="cell-message">
                    <?php if (($item['link_url'] ?? '') !== null && $item['link_url'] !== ''): ?>
                        <a href="<?= e((string) $item['link_url']) ?>" target="_blank" rel="noopener"><?= e((string) $item['message']) ?></a>
                    <?php else: ?>
                        <?= e((string) $item['message']) ?>
                    <?php endif; ?>
                </td>
                <td>
                    <form method="post" action="<?= e(route('admin.announcements.toggle', ['id' => $item['id']])) ?>" class="inline-form">
                        <?= csrf_field() ?>
                        <button type="submit" class="badge badge-toggle <?= ((int) $item['is_active']) === 1 ? 'badge-active' : 'badge-draft' ?>">
                            <?= ((int) $item['is_active']) === 1 ? e(trans('common.active')) : e(trans('common.inactive')) ?>
                        </button>
                    </form>
                </td>
                <td><?= e((string) $item['placement']) ?></td>
                <td><span class="badge badge-style-<?= e((string) $item['style']) ?>"><?= e((string) $item['style']) ?></span></td>
                <td class="cell-schedule">
                    <?php if (!empty($item['starts_at']) || !empty($item['ends_at'])): ?>
                        <?= e((string) ($item['starts_at'] ?? '…')) ?> → <?= e((string) ($item['ends_at'] ?? '…')) ?>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
                <td class="row-actions">
                    <a class="btn btn-sm btn-secondary" href="<?= e(route('admin.announcements.edit', ['id' => $item['id']])) ?>"><?= e(trans('common.edit')) ?></a>
                    <form method="post" action="<?= e(route('admin.announcements.destroy', ['id' => $item['id']])) ?>" class="inline-form"
                          onsubmit="return confirm('OK?');">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-danger"><?= e(trans('common.delete')) ?></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if ($items === []): ?>
            <tr>
                <td colspan="7" class="cell-empty"><?= e(trans('announcements.empty')) ?></td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
