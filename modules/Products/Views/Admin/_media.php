<?php
/** @var \Modules\Products\Models\Product $product */
/** @var array<string, array<int, array<string, mixed>>> $mediaSections */

$productId = (int) $product->id;
$code = (string) ($product->model_code ?? '');
$imageSections = [
    'photos' => ['label' => trans('products.section_photos'), 'hint' => trans('products.section_photos_hint')],
    'drawings' => ['label' => trans('products.section_drawings'), 'hint' => trans('products.section_drawings_hint')],
    'situ' => ['label' => trans('products.section_situ'), 'hint' => trans('products.section_situ_hint')],
];
$mediaUrl = static fn (string $name, array $params = []): string => route('admin.products.media.' . $name, $params);
?>
<div class="media-manager" data-media-root
     data-upload-url="<?= e($mediaUrl('store', ['id' => $productId])) ?>">
    <div class="media-manager-head">
        <p class="field-help"><?= e(trans('products.images_intro')) ?></p>
        <button type="button" class="btn btn-primary btn-sm" data-media-add><?= e(trans('products.media_add')) ?></button>
    </div>

    <?php foreach ($imageSections as $sectionKey => $sectionMeta): ?>
        <?php $items = $mediaSections[$sectionKey] ?? []; ?>
        <section class="media-section" data-media-section="<?= e($sectionKey) ?>">
            <header class="media-section-head">
                <h4><?= e($sectionMeta['label']) ?> <span class="badge badge-ghost"><?= count($items) ?></span></h4>
                <p class="field-help"><?= e($sectionMeta['hint']) ?></p>
                <?php if ($items !== []): ?>
                    <button type="button" class="btn btn-sm btn-ghost" data-media-replace-all
                            data-url="<?= e($mediaUrl('replace-section', ['id' => $productId])) ?>"
                            data-section="<?= e($sectionKey) ?>"
                            data-confirm="<?= e(trans('products.media_replace_all_confirm')) ?>"><?= e(trans('products.media_replace_all')) ?></button>
                <?php endif; ?>
            </header>

            <?php if ($items === []): ?>
                <p class="media-empty"><?= e(trans('products.media_empty')) ?></p>
            <?php else: ?>
                <div class="media-grid">
                    <?php foreach ($items as $index => $item): ?>
                        <?php
                        $attachmentId = (int) ($item['attachment_id'] ?? 0);
                        $isPrimary = (int) ($item['is_primary'] ?? 0) === 1 && $sectionKey === 'photos';
                        $fileName = (string) ($item['filename'] ?? $item['original_name'] ?? '');
                        $urls = [
                            'replace' => $mediaUrl('replace', ['id' => $productId, 'attachmentId' => $attachmentId]),
                            'destroy' => $mediaUrl('destroy', ['id' => $productId, 'attachmentId' => $attachmentId]),
                            'primary' => $mediaUrl('primary', ['id' => $productId, 'attachmentId' => $attachmentId]),
                            'move' => $mediaUrl('move', ['id' => $productId, 'attachmentId' => $attachmentId]),
                            'order' => $mediaUrl('order', ['id' => $productId, 'attachmentId' => $attachmentId]),
                        ];
                        ?>
                        <figure class="media-card<?= $isPrimary ? ' is-primary' : '' ?>" data-attachment="<?= $attachmentId ?>">
                            <div class="media-card-media">
                                <button type="button" class="adm-thumb" data-lightbox="product"
                                        data-attachment-id="<?= $attachmentId ?>"
                                        data-product-id="<?= $productId ?>"
                                        data-path="<?= e((string) $item['path']) ?>"
                                        data-section="<?= e($sectionKey) ?>"
                                        data-primary="<?= $isPrimary ? '1' : '0' ?>"
                                        data-caption="<?= e($code . ' · ' . $sectionMeta['label']) ?>"
                                        data-url-replace="<?= e($urls['replace']) ?>"
                                        data-url-destroy="<?= e($urls['destroy']) ?>"
                                        data-url-primary="<?= e($urls['primary']) ?>"
                                        data-url-move="<?= e($urls['move']) ?>">
                                    <img src="<?= e(asset((string) $item['path'])) ?>" alt="<?= e((string) ($item['original_name'] ?? '')) ?>" loading="lazy" decoding="async">
                                </button>
                                <?php if ($isPrimary): ?>
                                    <span class="media-card-flag"><?= e(trans('products.media_main')) ?></span>
                                <?php endif; ?>
                            </div>
                            <figcaption class="media-card-name" title="<?= e($fileName) ?>"><?= e($fileName) ?></figcaption>
                            <div class="media-card-actions">
                                <button type="button" class="btn btn-sm btn-ghost" data-media-order="up"
                                        data-url="<?= e($urls['order']) ?>" title="<?= e(trans('products.media_up')) ?>"<?= $index === 0 ? ' disabled' : '' ?>>↑</button>
                                <button type="button" class="btn btn-sm btn-ghost" data-media-order="down"
                                        data-url="<?= e($urls['order']) ?>" title="<?= e(trans('products.media_down')) ?>"<?= $index === count($items) - 1 ? ' disabled' : '' ?>>↓</button>
                                <?php if ($sectionKey === 'photos' && !$isPrimary): ?>
                                    <button type="button" class="btn btn-sm btn-secondary" data-media-primary
                                            data-url="<?= e($urls['primary']) ?>" title="<?= e(trans('products.media_set_main')) ?>">★</button>
                                <?php endif; ?>
                                <button type="button" class="btn btn-sm btn-danger" data-media-delete
                                        data-url="<?= e($urls['destroy']) ?>"
                                        data-confirm="<?= e(trans('products.media_remove_confirm')) ?>"
                                        title="<?= e(trans('products.media_remove')) ?>">×</button>
                            </div>
                        </figure>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    <?php endforeach; ?>
</div>
