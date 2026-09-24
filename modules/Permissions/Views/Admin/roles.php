<?php
$view->layout('layouts.admin');
?>
<div class="grid grid-3">
    <?php foreach ($roles as $role): $granted = $role->permissionKeys(); ?>
        <div class="card">
            <div class="card-header"><h3 class="card-title"><?= e($role->name) ?></h3></div>
            <div class="card-body">
                <form method="post" action="<?= e(route('admin.roles.updatePermissions', ['id' => $role->id])) ?>" class="stack">
                    <?= csrf_field() ?>
                    <?php foreach ($permissions as $permission): ?>
                        <label class="check-row">
                            <input type="checkbox" name="permissions[]" value="<?= (int) $permission->id ?>"
                                   <?= in_array($permission->key, $granted, true) ? 'checked' : '' ?>>
                            <?= e($permission->key) ?>
                        </label>
                    <?php endforeach; ?>
                    <?= $view->component('button', ['label' => trans('common.save'), 'variant' => 'secondary', 'size' => 'sm', 'type' => 'submit']) ?>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
</div>
