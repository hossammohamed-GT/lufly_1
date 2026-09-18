<?php
/** @var Core\View\View $view */
$view->layout('layouts.admin');
/** @var \Modules\Users\Models\User|null $user */
/** @var array<int, \Modules\Permissions\Models\Role> $roles */
$action = $user === null ? route('admin.users.store') : route('admin.users.update', ['id' => $user->id]);
$userRoles = $user !== null ? $user->roleNames() : [];
?>
<form method="post" action="<?= e($action) ?>" class="stack admin-form">
    <?= csrf_field() ?>
    <div class="grid grid-2">
        <?= $view->component('input', ['name' => 'name', 'label' => trans('common.name'), 'value' => $user->name ?? '', 'required' => true]) ?>
        <?= $view->component('input', ['name' => 'email', 'label' => trans('common.email'), 'type' => 'email', 'value' => $user->email ?? '', 'required' => true]) ?>
        <?= $view->component('input', ['name' => 'password', 'label' => trans('common.password'), 'type' => 'password', 'required' => $user === null]) ?>
        <?= $view->component('input', ['name' => 'password_confirmation', 'label' => trans('common.password_confirmation'), 'type' => 'password', 'required' => $user === null]) ?>
        <?= $view->component('input', ['name' => 'phone', 'label' => trans('common.phone'), 'value' => $user->phone ?? '']) ?>
        <div class="field">
            <label class="field-label" for="status"><?= e(trans('common.status')) ?></label>
            <?php $status = $user->status ?? 'active'; ?>
            <select class="input" id="status" name="status">
                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>><?= e(trans('common.active')) ?></option>
                <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>><?= e(trans('common.inactive')) ?></option>
            </select>
        </div>
    </div>

    <fieldset>
        <legend><?= e(trans('common.roles_permissions')) ?></legend>
        <?php foreach ($roles as $role): ?>
            <label class="check-row">
                <input type="checkbox" name="roles[]" value="<?= (int) $role->id ?>" <?= in_array($role->name, $userRoles, true) ? 'checked' : '' ?>>
                <?= e($role->name) ?><?= !empty($role->description) ? ' (' . e($role->description) . ')' : '' ?>
            </label>
        <?php endforeach; ?>
    </fieldset>

    <div class="form-actions">
        <?= $view->component('button', ['label' => trans('common.save'), 'variant' => 'primary', 'type' => 'submit']) ?>
        <?= $view->component('button', ['label' => trans('common.cancel'), 'variant' => 'ghost', 'href' => route('admin.users.index')]) ?>
    </div>
</form>
