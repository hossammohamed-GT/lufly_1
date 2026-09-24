<?php
$view->layout('layouts.admin');
$action = $announcement === null
    ? route('admin.announcements.store')
    : route('admin.announcements.update', ['id' => $announcement->id]);

$locales = $translator->locales();
$localeNames = $translator->supported();
$placement = $announcement->placement ?? 'topbar';
$style = $announcement->style ?? 'promo';
$isActive = $announcement === null ? 1 : (int) $announcement->is_active;
?>
<form method="post" action="<?= e($action) ?>" class="stack admin-form">
    <?= csrf_field() ?>

    <div class="grid grid-2">
        <div class="field">
            <label class="field-label" for="placement"><?= e(trans('announcements.placement')) ?></label>
            <select class="input" id="placement" name="placement">
                <option value="topbar" <?= $placement === 'topbar' ? 'selected' : '' ?>><?= e(trans('announcements.placement_topbar')) ?></option>
                <option value="home_banner" <?= $placement === 'home_banner' ? 'selected' : '' ?>><?= e(trans('announcements.placement_home')) ?></option>
            </select>
        </div>
        <div class="field">
            <label class="field-label" for="style"><?= e(trans('announcements.style')) ?></label>
            <select class="input" id="style" name="style">
                <?php foreach (['promo', 'info', 'warning'] as $option): ?>
                    <option value="<?= $option ?>" <?= $style === $option ? 'selected' : '' ?>><?= e(trans('announcements.style_' . $option)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <h2><?= e(trans('common.translations')) ?></h2>
    <?php foreach ($locales as $locale): $tr = $translations[$locale] ?? []; ?>
        <fieldset class="translation-set">
            <legend><?= e(strtoupper($locale)) ?> - <?= e($localeNames[$locale] ?? $locale) ?></legend>
            <div class="field">
                <label class="field-label" for="message_<?= e($locale) ?>"><?= e(trans('announcements.message')) ?> (<?= e(strtoupper($locale)) ?>)<?= $locale === 'en' ? ' *' : '' ?></label>
                <input class="input" id="message_<?= e($locale) ?>" name="message_<?= e($locale) ?>"
                       type="text" maxlength="500"
                       value="<?= e((string) ($tr['message'] ?? '')) ?>"
                       placeholder="<?= e(trans('announcements.message_placeholder')) ?>"
                       <?= $locale === 'en' ? 'required' : '' ?>>
            </div>
            <?= $view->component('input', [
                'name' => 'cta_' . $locale,
                'label' => trans('announcements.cta') . ' (' . strtoupper($locale) . ')',
                'value' => (string) ($tr['cta_label'] ?? ''),
            ]) ?>
        </fieldset>
    <?php endforeach; ?>

    <div class="grid grid-2">
        <?= $view->component('input', ['name' => 'link_url', 'label' => trans('announcements.link_url'), 'value' => (string) ($announcement->link_url ?? ''), 'placeholder' => 'https://...']) ?>
        <div class="field">
            <label class="field-label" for="is_active"><?= e(trans('common.status')) ?></label>
            <select class="input" id="is_active" name="is_active">
                <option value="1" <?= $isActive === 1 ? 'selected' : '' ?>><?= e(trans('common.active')) ?></option>
                <option value="0" <?= $isActive === 0 ? 'selected' : '' ?>><?= e(trans('common.inactive')) ?></option>
            </select>
        </div>
    </div>

    <div class="grid grid-2">
        <div class="field">
            <label class="field-label" for="starts_at"><?= e(trans('announcements.starts_at')) ?></label>
            <input class="input" id="starts_at" name="starts_at" type="datetime-local"
                   value="<?= e(str_replace(' ', 'T', substr((string) ($announcement->starts_at ?? ''), 0, 16))) ?>">
        </div>
        <div class="field">
            <label class="field-label" for="ends_at"><?= e(trans('announcements.ends_at')) ?></label>
            <input class="input" id="ends_at" name="ends_at" type="datetime-local"
                   value="<?= e(str_replace(' ', 'T', substr((string) ($announcement->ends_at ?? ''), 0, 16))) ?>">
        </div>
    </div>

    <div class="form-actions">
        <?= $view->component('button', ['label' => trans('common.save'), 'variant' => 'primary', 'type' => 'submit']) ?>
        <?= $view->component('button', ['label' => trans('common.cancel'), 'variant' => 'ghost', 'href' => route('admin.announcements.index')]) ?>
    </div>
</form>
