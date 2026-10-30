<?php
/** @var Core\View\View $view */
$view->layout('layouts.admin');
/** @var array<int, array{source_id: int, members: array<int, array{id: int, model_code: string, name: string, finish_key: string, finish_label: string, live: bool, cover: string}>}> $families */
/** @var string $locale */
?>
<div class="adm-families">
    <p class="field-help"><?= e(trans('products.families_intro')) ?></p>
    <p class="field-help"><?= e(trans('products.families_new_hint')) ?></p>

    <?php if ($families === []): ?>
        <p class="media-empty"><?= e(trans('products.families_empty')) ?></p>
    <?php endif; ?>

    <?php foreach ($families as $family): ?>
        <section class="adm-family-block" data-family-block>
            <header class="adm-family-block-head">
                <strong><?= e($family['members'][0]['name'] !== '' ? $family['members'][0]['name'] : $family['members'][0]['model_code']) ?></strong>
                <span class="badge badge-ghost"><?= count($family['members']) ?></span>
                <span class="adm-family-block-spacer"></span>
                <button type="button" class="btn btn-sm btn-secondary" data-add-color data-reload
                        data-url="<?= e(route('admin.products.add-color', ['id' => $family['source_id']])) ?>"
                        data-confirm="<?= e(trans('products.family_add_confirm')) ?>">+ <?= e(trans('products.family_add')) ?></button>
            </header>
            <div class="adm-family-members">
                <?php foreach ($family['members'] as $member): ?>
                    <div class="adm-family-member">
                        <?php if ($member['cover'] !== ''): ?>
                            <img class="adm-family-member-img" src="<?= e(asset($member['cover'])) ?>" alt="<?= e($member['name']) ?>" loading="lazy">
                        <?php else: ?>
                            <span class="adm-family-member-img adm-noimg"><?= e(trans('products.no_image')) ?></span>
                        <?php endif; ?>
                        <div class="adm-family-member-meta">
                            <strong><?= e($member['model_code']) ?></strong>
                            <span><?= e($member['name']) ?></span>
                            <span class="adm-family-member-finish">
                                <i class="adm-swatch adm-swatch--<?= e($member['finish_key']) ?>"></i>
                                <?= e($member['finish_label'] !== '' ? $member['finish_label'] : trans('products.no_finish')) ?>
                            </span>
                            <span class="badge badge-<?= $member['live'] ? 'active' : 'inactive' ?>"><?= e(trans($member['live'] ? 'products.visible' : 'products.hidden')) ?></span>
                        </div>
                        <a class="btn btn-sm btn-secondary" href="<?= e(route('admin.products.index')) ?>?q=<?= e(urlencode($member['model_code'])) ?>"><?= e(trans('products.families_open')) ?></a>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>
</div>
