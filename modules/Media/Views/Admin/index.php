<?php
/** @var Core\View\View $view */
$view->layout('layouts.admin');
/** @var array<int, \Modules\Media\Models\Media> $items */
?>
<form method="post" action="<?= e(route('admin.media.store')) ?>" enctype="multipart/form-data" class="stack admin-form">
    <?= csrf_field() ?>
    <div class="grid grid-2">
        <div class="field">
            <label class="field-label" for="file">File</label>
            <input class="input" type="file" id="file" name="file" required>
        </div>
        <?= $view->component('input', ['name' => 'collection', 'label' => 'Collection', 'value' => 'general']) ?>
    </div>
    <?= $view->component('button', ['label' => trans('common.uploaded'), 'variant' => 'primary', 'type' => 'submit']) ?>
</form>

<table class="table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Preview</th>
            <th>File</th>
            <th>Type</th>
            <th>Size (KB)</th>
            <th>Collection</th>
            <th><?= e(trans('common.actions')) ?></th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($items)): ?>
            <tr>
                <td colspan="7" style="text-align: center; color: var(--ds-text-muted); padding: 2rem;">
                    <?= e(trans('common.no_results')) ?>
                </td>
            </tr>
        <?php else: ?>
            <?php foreach ($items as $media):
                $isImage = str_starts_with((string) $media->mime_type, 'image/')
                    || in_array(strtolower((string) $media->extension), ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg', 'avif'], true);
                $mediaUrl = asset($media->path);
            ?>
                <tr>
                    <td><?= (int) $media->id ?></td>
                    <td style="width: 60px;">
                        <?php if ($isImage): ?>
                            <a href="<?= $mediaUrl ?>" target="_blank" rel="noopener" title="<?= e($media->original_name) ?>">
                                <img src="<?= $mediaUrl ?>" alt="<?= e($media->original_name) ?>" loading="lazy" style="width: 48px; height: 48px; object-fit: cover; border-radius: 6px; border: 1px solid var(--ds-border, #e2e8f0); display: block; background: var(--ds-surface, #fff);">
                            </a>
                        <?php else: ?>
                            <div style="width: 48px; height: 48px; border-radius: 6px; border: 1px solid var(--ds-border, #e2e8f0); display: flex; align-items: center; justify-content: center; background: var(--ds-surface-alt, #f1f5f9); font-size: 10px; font-weight: bold; color: var(--ds-text-muted, #64748b);">
                                <?= e(strtoupper((string) $media->extension ?: 'FILE')) ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="font-weight: var(--weight-medium, 500);"><?= e($media->original_name) ?></div>
                        <small style="color: var(--ds-text-muted); font-size: 0.75rem; word-break: break-all;"><?= e($media->path) ?></small>
                    </td>
                    <td><span class="badge"><?= e($media->mime_type) ?></span></td>
                    <td><?= (int) round(((int) $media->size) / 1024) ?></td>
                    <td><?= e($media->collection) ?></td>
                    <td>
                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                            <button type="button" class="btn btn-sm btn-ghost" onclick="const btn = this; navigator.clipboard.writeText('<?= $mediaUrl ?>').then(() => { const old = btn.textContent; btn.textContent = 'Copied!'; setTimeout(() => btn.textContent = old, 1500); });" title="Copy URL">
                                Copy URL
                            </button>
                            <form method="post" action="<?= e(route('admin.media.destroy', ['id' => $media->id])) ?>" class="inline-form"
                                  onsubmit="return confirm('Are you sure you want to delete this file?');">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-danger"><?= e(trans('common.delete')) ?></button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php if (isset($paginator)): ?>
    <?= $view->component('pagination', ['paginator' => $paginator]) ?>
<?php endif; ?>

