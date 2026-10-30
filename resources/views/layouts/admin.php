<?php
/** @var Core\View\View $view */
/** @var string $content */
/** @var string $title */
$translator = $translator ?? null;
$locale = $translator instanceof \Core\Localization\Translator ? $translator->getLocale() : (string) config('localization.default', 'en');
$basePath = rtrim((string) parse_url(url('/'), PHP_URL_PATH), '/');
$direction = in_array($locale, ['ar', 'he', 'fa', 'ur'], true) ? 'rtl' : 'ltr';
$currentPath = trim((string) request()->path(), '/');
$navItems = [
    ['href' => route('admin.dashboard'), 'label' => trans('common.dashboard'), 'active' => $currentPath === 'admin'],
    ['href' => route('admin.products.index'), 'label' => trans('common.products'), 'active' => str_starts_with($currentPath, 'admin/products')],
    ['href' => route('admin.categories.index'), 'label' => trans('common.categories'), 'active' => str_starts_with($currentPath, 'admin/categories')],
    ['href' => route('admin.collections.index'), 'label' => trans('common.collections'), 'active' => str_starts_with($currentPath, 'admin/collections')],
    ['href' => route('admin.brands.index'), 'label' => trans('common.brands'), 'active' => str_starts_with($currentPath, 'admin/brands')],
];
?>
<!DOCTYPE html>
<html lang="<?= e($locale) ?>" dir="<?= e($direction) ?>" data-theme="light" data-base="<?= e($basePath) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<title>Lufly</title>
<link rel="icon" type="image/png" href="<?= e(asset('images/logo.png')) ?>">
<script>
/* theme before first paint */
try {
    var stored = localStorage.getItem('lufly-theme');
    var dark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    document.documentElement.setAttribute('data-theme', stored || (dark ? 'dark' : 'light'));
} catch (error) { /* storage unavailable */ }
</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="<?= e(asset('frontend/design-system/style.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('frontend/admin/admin.css')) ?>">
</head>
<body class="ds-app admin-body">
<div class="admin-shell">
    <aside class="admin-sidebar">
        <div class="admin-brand"><?= e($appName) ?></div>
        <nav class="admin-nav">
            <?php foreach ($navItems as $item): ?>
                <a href="<?= e($item['href']) ?>"<?= $item['active'] ? ' class="is-active" aria-current="page"' : '' ?>><?= e($item['label']) ?></a>
            <?php endforeach; ?>
        </nav>
    </aside>
    <div class="admin-main">
        <header class="admin-topbar">
            <h1 class="admin-title"><?= e($title ?? '') ?></h1>
            <div class="admin-topbar-actions">
                <?= $view->renderFile($view->resolvePath('components.theme-switcher'), []) ?>
                <a class="btn btn-ghost" href="<?= e(route('home')) ?>"><?= e(trans('common.view_site')) ?></a>
                <a class="btn btn-ghost" href="<?= e(route('admin.password.edit')) ?>"><?= e(trans('common.change_password')) ?></a>
                <form method="post" action="<?= e(url('/logout')) ?>" class="inline-form">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-ghost"><?= e(trans('auth.logout')) ?></button>
                </form>
            </div>
        </header>
        <div class="admin-content">
            <?= $view->renderFile($view->resolvePath('components.alert'), []) ?>
            <?= $content ?>
        </div>
    </div>
</div>

<?php /* Image lightbox: enlarge + replace / move / set-main / delete. */ ?>
<div class="adm-lightbox" data-lightbox hidden role="dialog" aria-modal="true" aria-label="<?= e(trans('products.images_title')) ?>">
    <div class="adm-lightbox-backdrop" data-lightbox-close></div>
    <figure class="adm-lightbox-box">
        <img class="adm-lightbox-img" data-lightbox-img alt="">
        <figcaption class="adm-lightbox-caption" data-lightbox-caption></figcaption>
        <div class="adm-lightbox-tools">
            <label class="adm-lightbox-section">
                <span><?= e(trans('products.lightbox_section')) ?></span>
                <select class="input input-sm" data-lightbox-section>
                    <option value="photos"><?= e(trans('products.section_photos')) ?></option>
                    <option value="drawings"><?= e(trans('products.section_drawings')) ?></option>
                    <option value="situ"><?= e(trans('products.section_situ')) ?></option>
                </select>
            </label>
            <span class="adm-lightbox-actions">
                <button type="button" class="btn btn-sm btn-secondary" data-lightbox-primary>★ <?= e(trans('products.media_set_main')) ?></button>
                <button type="button" class="btn btn-sm btn-secondary" data-lightbox-replace><?= e(trans('products.media_replace')) ?></button>
                <button type="button" class="btn btn-sm btn-danger" data-lightbox-delete><?= e(trans('common.delete')) ?></button>
                <button type="button" class="btn btn-sm btn-ghost" data-lightbox-close><?= e(trans('common.close')) ?></button>
            </span>
        </div>
    </figure>
</div>

<?php /* Image-type chooser: asked before every upload. */ ?>
<div class="adm-chooser" data-type-chooser hidden role="dialog" aria-modal="true" aria-label="<?= e(trans('products.pick_type_title')) ?>">
    <div class="adm-chooser-backdrop" data-type-cancel></div>
    <div class="adm-chooser-box">
        <h3><?= e(trans('products.pick_type_title')) ?></h3>
        <p class="field-help"><?= e(trans('products.pick_type_hint')) ?></p>
        <div class="adm-chooser-grid">
            <button type="button" class="adm-chooser-card" data-type-choice="photos">
                <strong><?= e(trans('products.section_photos')) ?></strong>
                <span><?= e(trans('products.section_photos_hint')) ?></span>
            </button>
            <button type="button" class="adm-chooser-card" data-type-choice="drawings">
                <strong><?= e(trans('products.section_drawings')) ?></strong>
                <span><?= e(trans('products.section_drawings_hint')) ?></span>
            </button>
            <button type="button" class="adm-chooser-card" data-type-choice="situ">
                <strong><?= e(trans('products.section_situ')) ?></strong>
                <span><?= e(trans('products.section_situ_hint')) ?></span>
            </button>
        </div>
        <div class="form-actions">
            <button type="button" class="btn btn-sm btn-ghost" data-type-cancel><?= e(trans('common.cancel')) ?></button>
        </div>
    </div>
</div>

<?php /* Drill-down category picker (shared by cards + category parents). */ ?>
<div class="adm-catpop" data-catpop hidden role="dialog" aria-label="<?= e(trans('products.choose_category')) ?>">
    <div class="adm-catpop-head">
        <strong><?= e(trans('products.choose_category')) ?></strong>
        <button type="button" class="btn btn-sm btn-ghost" data-catpop-close>×</button>
    </div>
    <div class="adm-catpop-crumbs" data-catpop-crumbs></div>
    <div class="adm-catpop-list" data-catpop-list></div>
    <div class="adm-catpop-foot">
        <button type="button" class="btn btn-sm btn-secondary" data-catpop-clear><?= e(trans('products.category_none')) ?></button>
        <button type="button" class="btn btn-sm btn-ghost" data-catpop-close><?= e(trans('common.cancel')) ?></button>
    </div>
</div>

<?php /* Card feature popover: pick a ready-made preset, type only the value. */ ?>
<div class="adm-featpop" data-featpop hidden role="dialog" aria-label="<?= e(trans('products.feature_pick_title')) ?>"
     data-preset-placeholder="<?= e(trans('products.feature_pick_placeholder')) ?>">
    <div class="adm-featpop-head">
        <strong><?= e(trans('products.feature_pick_title')) ?></strong>
        <button type="button" class="btn btn-sm btn-ghost" data-featpop-close>×</button>
    </div>
    <div class="adm-featpop-body">
        <label class="field"><span class="field-label"><?= e(trans('products.feature_preset')) ?></span>
            <span class="icon-select-wrap">
                <select class="input" data-featpop-preset></select>
                <span class="icon-preview" data-featpop-icon></span>
            </span></label>
        <label class="field"><span class="field-label"><?= e(trans('products.feature_value')) ?></span>
            <input class="input" type="text" maxlength="255" data-featpop-value></label>
    </div>
    <div class="adm-featpop-foot">
        <button type="button" class="btn btn-sm btn-danger" data-featpop-delete><?= e(trans('common.delete')) ?></button>
        <span class="adm-featpop-spacer"></span>
        <button type="button" class="btn btn-sm btn-ghost" data-featpop-close><?= e(trans('common.cancel')) ?></button>
        <button type="button" class="btn btn-sm btn-secondary" data-featpop-save><?= e(trans('common.save')) ?></button>
    </div>
</div>

<?php /* Card finish popover: tick canonical finishes from the system catalogue. */ ?>
<div class="adm-finishpop" data-finishpop hidden role="dialog" aria-label="<?= e(trans('products.finish_picker')) ?>">
    <div class="adm-finishpop-head">
        <strong><?= e(trans('products.finish_picker')) ?></strong>
        <button type="button" class="btn btn-sm btn-ghost" data-finishpop-close>×</button>
    </div>
    <div class="adm-finishpop-list" data-finishpop-list></div>
    <div class="adm-finishpop-foot">
        <button type="button" class="btn btn-sm btn-ghost" data-finishpop-clear><?= e(trans('common.remove')) ?></button>
        <span class="adm-finishpop-spacer"></span>
        <button type="button" class="btn btn-sm btn-ghost" data-finishpop-close><?= e(trans('common.cancel')) ?></button>
        <button type="button" class="btn btn-sm btn-secondary" data-finishpop-save><?= e(trans('common.save')) ?></button>
    </div>
</div>

<?php /* Product full editor: one centered modal, filled per product via AJAX. */ ?>
<div class="adm-editor-modal" data-editor-modal hidden role="dialog" aria-modal="true" aria-label="<?= e(trans('products.expand_editor')) ?>">
    <div class="adm-editor-backdrop" data-editor-close></div>
    <div class="adm-editor-panel">
        <div class="adm-editor-head">
            <strong class="adm-editor-title" data-editor-title><?= e(trans('products.expand_editor')) ?></strong>
            <button type="button" class="btn btn-sm btn-ghost" data-editor-close aria-label="<?= e(trans('common.close')) ?>">×</button>
        </div>
        <div class="adm-editor-body" data-editor-body></div>
    </div>
</div>

<?php /* Quick-add wizard: the easy 4-step way to add a product (basics only). */ ?>
<div class="adm-wizard" data-wizard hidden role="dialog" aria-modal="true" aria-label="<?= e(trans('common.create_product')) ?>"
     data-msg-name-required="<?= e(trans('products.wizard_name_required')) ?>"
     data-msg-created="<?= e(trans('products.wizard_created')) ?>"
     data-preset-placeholder="<?= e(trans('products.feature_pick_placeholder')) ?>"
     data-value-label="<?= e(trans('products.feature_value')) ?>">
    <div class="adm-wizard-backdrop" data-wizard-close></div>
    <div class="adm-wizard-panel">
        <div class="adm-wizard-head">
            <strong><?= e(trans('common.create_product')) ?></strong>
            <button type="button" class="btn btn-sm btn-ghost" data-wizard-close aria-label="<?= e(trans('common.close')) ?>">×</button>
        </div>
        <ol class="adm-wizard-steps">
            <li data-wizard-step-label="1" class="is-active"><?= e(trans('products.wizard_step_basics')) ?></li>
            <li data-wizard-step-label="2"><?= e(trans('products.wizard_step_photos')) ?></li>
            <li data-wizard-step-label="3"><?= e(trans('products.wizard_step_look')) ?></li>
            <li data-wizard-step-label="4"><?= e(trans('products.wizard_step_publish')) ?></li>
        </ol>
        <div class="adm-wizard-body">
            <section data-wizard-panel="1">
                <label class="field"><span class="field-label"><?= e(trans('products.wizard_name')) ?> *</span>
                    <input class="input" type="text" maxlength="255" data-wizard-name="en" placeholder="<?= e(trans('products.wizard_name_hint')) ?>"></label>
                <details class="adm-wizard-more">
                    <summary><?= e(trans('products.wizard_name_languages')) ?></summary>
                    <label class="field"><span class="field-label">TR</span>
                        <input class="input" type="text" maxlength="255" data-wizard-name="tr"></label>
                    <label class="field"><span class="field-label">CS</span>
                        <input class="input" type="text" maxlength="255" data-wizard-name="cs"></label>
                </details>
                <div class="adm-wizard-grid">
                    <label class="field"><span class="field-label"><?= e(trans('products.wizard_code')) ?></span>
                        <input class="input" type="text" maxlength="100" data-wizard-code placeholder="<?= e(trans('products.wizard_code_hint')) ?>"></label>
                    <label class="field"><span class="field-label"><?= e(trans('common.price')) ?></span>
                        <input class="input" type="number" min="0" step="any" data-wizard-price></label>
                </div>
                <div class="field"><span class="field-label"><?= e(trans('common.category')) ?></span>
                    <button type="button" class="input adm-catbtn is-empty" data-cat-picker data-wizard-category data-category-id="0"
                            title="<?= e(trans('products.choose_category')) ?>">
                        <span data-cat-label><?= e(trans('products.choose_category')) ?></span>
                        <span class="adm-catbtn-caret">▾</span>
                    </button>
                    <p class="field-help"><?= e(trans('products.wizard_category_hint')) ?></p>
                </div>
            </section>
            <section data-wizard-panel="2" hidden>
                <div class="adm-wizard-zone">
                    <div class="adm-wizard-zone-head">
                        <strong><?= e(trans('products.section_photos')) ?></strong>
                        <button type="button" class="btn btn-sm btn-secondary" data-wizard-upload="photos"><?= e(trans('products.media_add')) ?></button>
                    </div>
                    <p class="field-help"><?= e(trans('products.section_photos_hint')) ?></p>
                    <div class="adm-wizard-thumbs" data-wizard-thumbs="photos"></div>
                </div>
                <div class="adm-wizard-zone">
                    <div class="adm-wizard-zone-head">
                        <strong><?= e(trans('products.section_drawings')) ?></strong>
                        <button type="button" class="btn btn-sm btn-secondary" data-wizard-upload="drawings"><?= e(trans('products.media_add')) ?></button>
                    </div>
                    <p class="field-help"><?= e(trans('products.section_drawings_hint')) ?></p>
                    <div class="adm-wizard-thumbs" data-wizard-thumbs="drawings"></div>
                </div>
                <p class="field-help"><?= e(trans('products.wizard_photos_optional')) ?></p>
            </section>
            <section data-wizard-panel="3" hidden>
                <div class="field"><span class="field-label"><?= e(trans('products.finish_picker')) ?></span>
                    <div class="adm-wizard-finishes" data-wizard-finishes></div>
                </div>
                <div class="field"><span class="field-label"><?= e(trans('products.section_features')) ?></span>
                    <div data-wizard-features></div>
                    <button type="button" class="btn btn-sm btn-ghost" data-wizard-feature-add>+ <?= e(trans('products.feature_add')) ?></button>
                    <p class="field-help"><?= e(trans('products.wizard_features_hint')) ?></p>
                </div>
            </section>
            <section data-wizard-panel="4" hidden>
                <div class="adm-wizard-summary" data-wizard-summary></div>
                <div class="adm-wizard-publish">
                    <label class="adm-wizard-choice">
                        <input type="radio" name="wizard_publish" value="active" data-wizard-publish checked>
                        <span><strong><?= e(trans('products.wizard_publish_show')) ?></strong><small><?= e(trans('products.wizard_publish_show_hint')) ?></small></span>
                    </label>
                    <label class="adm-wizard-choice">
                        <input type="radio" name="wizard_publish" value="draft" data-wizard-publish>
                        <span><strong><?= e(trans('products.wizard_publish_draft')) ?></strong><small><?= e(trans('products.wizard_publish_draft_hint')) ?></small></span>
                    </label>
                </div>
                <p class="field-help"><?= e(trans('products.wizard_finish_hint')) ?></p>
            </section>
        </div>
        <p class="adm-wizard-error" data-wizard-error hidden></p>
        <div class="adm-wizard-foot">
            <button type="button" class="btn btn-sm btn-ghost" data-wizard-back hidden><?= e(trans('products.wizard_back')) ?></button>
            <span class="adm-wizard-spacer"></span>
            <button type="button" class="btn btn-sm btn-ghost" data-wizard-close><?= e(trans('common.cancel')) ?></button>
            <button type="button" class="btn btn-sm btn-secondary" data-wizard-next><?= e(trans('products.wizard_next')) ?></button>
            <button type="button" class="btn btn-sm btn-secondary" data-wizard-finish hidden><?= e(trans('products.wizard_finish')) ?></button>
        </div>
    </div>
</div>

<script src="<?= e(asset('frontend/js/app.js')) ?>"></script>
<script src="<?= e(asset('frontend/admin/admin.js')) ?>"></script>
</body>
</html>
