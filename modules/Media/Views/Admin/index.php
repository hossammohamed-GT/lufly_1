<?php
$view->layout('layouts.admin');
?>
<form method="post" action="<?= e(route('admin.media.store')) ?>" enctype="multipart/form-data" class="stack admin-form">
    <?= csrf_field() ?>
    <div class="grid grid-2">
        <div class="field">
            <label class="field-label" for="file"><?= e(trans('common.file') ?? 'File') ?></label>
            <input class="input" type="file" id="file" name="file" required>
        </div>
        <div class="field">
            <label class="field-label" for="media_collection_select">Collection</label>
            <select class="input" id="media_collection_select" name="collection" onchange="toggleNewCollectionInput(this)">
                <?php
                $collectionsList = $collections ?? [];
                $hasGeneral = false;
                foreach ($collectionsList as $col) {
                    if ($col['collection'] === 'general') {
                        $hasGeneral = true;
                        break;
                    }
                }
                ?>
                <?php foreach ($collectionsList as $col): ?>
                    <option value="<?= e($col['collection']) ?>" <?= $col['collection'] === ($collectionsList[0]['collection'] ?? '') ? 'selected' : '' ?>>
                        📁 <?= e($col['label'] ?? $col['collection']) ?> (<?= $col['count'] ?>)
                    </option>
                <?php endforeach; ?>
                <?php if (!$hasGeneral): ?>
                    <option value="general">📁 General</option>
                <?php endif; ?>
                <option value="__new__">➕ Create new collection...</option>
            </select>
            <div id="new_collection_wrapper" style="display: none; margin-top: 8px;">
                <input class="input" type="text" id="new_collection_input" name="new_collection" placeholder="Type new collection name (e.g. banners, showrooms, catalog)..." maxlength="50">
            </div>
        </div>
    </div>
    <?= $view->component('button', ['label' => trans('common.uploaded'), 'variant' => 'primary', 'type' => 'submit']) ?>
</form>

<script>
function toggleNewCollectionInput(select) {
    const wrap = document.getElementById('new_collection_wrapper');
    const input = document.getElementById('new_collection_input');
    if (!wrap || !input) return;
    if (select.value === '__new__') {
        wrap.style.display = 'block';
        input.focus();
        input.required = true;
    } else {
        wrap.style.display = 'none';
        input.required = false;
        input.value = '';
    }
}
</script>

<?php
$activeCol = $activeCollection ?? '';
$totalFiles = array_sum(array_column($collections ?? [], 'count'));
?>
<div class="media-filter-bar" style="display: flex; gap: 8px; flex-wrap: wrap; margin: 1.5rem 0 1rem 0; align-items: center;">
    <span style="font-size: 0.85rem; font-weight: 600; color: var(--ds-text-muted, #64748b); margin-right: 4px;">Collection:</span>
    <a href="<?= e(route('admin.media.index')) ?>"
       style="text-decoration: none; padding: 5px 12px; font-size: 0.82rem; font-weight: 500; border-radius: 20px; border: 1px solid <?= $activeCol === '' ? 'var(--ds-primary, #0f766e)' : 'var(--ds-border, #cbd5e1)' ?>; background: <?= $activeCol === '' ? 'var(--ds-primary, #0f766e)' : 'var(--ds-surface, #fff)' ?>; color: <?= $activeCol === '' ? '#fff' : 'var(--ds-text, #334155)' ?>;">
        All (<?= $totalFiles ?>)
    </a>
    <?php foreach (($collections ?? []) as $col): ?>
        <a href="<?= e(route('admin.media.index', ['collection' => $col['collection']])) ?>"
           style="text-decoration: none; padding: 5px 12px; font-size: 0.82rem; font-weight: 500; border-radius: 20px; border: 1px solid <?= $activeCol === $col['collection'] ? 'var(--ds-primary, #0f766e)' : 'var(--ds-border, #cbd5e1)' ?>; background: <?= $activeCol === $col['collection'] ? 'var(--ds-primary, #0f766e)' : 'var(--ds-surface, #fff)' ?>; color: <?= $activeCol === $col['collection'] ? '#fff' : 'var(--ds-text, #334155)' ?>;">
            📁 <?= e($col['label'] ?? $col['collection']) ?> (<?= $col['count'] ?>)
        </a>
    <?php endforeach; ?>
</div>

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
                    <td>
                        <a href="<?= e(route('admin.media.index', ['collection' => $media->collection])) ?>" class="badge badge-ghost" style="text-decoration: none; font-size: 0.8rem;" title="Filter by this collection">
                            📁 <?= e(ucwords(str_replace(['-', '_'], ' ', (string) $media->collection))) ?>
                        </a>
                    </td>
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

