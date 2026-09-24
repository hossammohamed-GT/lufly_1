<?php

declare(strict_types=1);

namespace Modules\Permissions\Repositories;

use App\Repositories\Repository;
use Modules\Permissions\Models\Role;

class RoleRepository extends Repository
{
    protected string $model = Role::class;

    protected bool $auditing = true;

    protected string $auditEntity = 'role';

    public function syncPermissions(int|string $roleId, array $permissionIds): void
    {
        $connection = Role::query()->connection();
        $connection->affect('DELETE FROM role_permissions WHERE role_id = ?', [(int) $roleId]);

        foreach ($permissionIds as $permissionId) {
            $connection->insert('role_permissions', [
                'role_id' => (int) $roleId,
                'permission_id' => (int) $permissionId,
            ]);
        }
    }

    public function findByName(string $name): ?Role
    {
        $role = Role::query()->where('name', $name)->first();

        return $role;
    }
}
