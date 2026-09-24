<?php

declare(strict_types=1);

namespace Modules\Permissions\Repositories;

use App\Repositories\Repository;
use Modules\Permissions\Models\Permission;

class PermissionRepository extends Repository
{
    protected string $model = Permission::class;

    public function findByKey(string $key): ?Permission
    {
        $permission = Permission::query()->where('key', $key)->first();

        return $permission;
    }
}
