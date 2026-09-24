<?php

declare(strict_types=1);

namespace Modules\Permissions\Services;

use Modules\Permissions\Repositories\PermissionRepository;
use Modules\Permissions\Repositories\RoleRepository;
use Modules\Users\Repositories\UserRepository;

class PermissionService
{
    public function __construct(
        private readonly RoleRepository $roles,
        private readonly PermissionRepository $permissions,
        private readonly UserRepository $users,
    ) {
    }

    public function permissionsForUser(int|string $userId): array
    {
        return $this->users->permissionsFor($userId);
    }

    public function syncRolePermissions(int $roleId, array $permissionIds): void
    {
        $this->roles->syncPermissions($roleId, $permissionIds);
    }

    public function syncUserRoles(int $userId, array $roleIds): void
    {
        $this->users->syncRoles($userId, $roleIds);
    }

    public function ensurePermission(string $key, string $name = ''): void
    {
        if ($this->permissions->findByKey($key) === null) {
            $this->permissions->create([
                'key' => $key,
                'name' => $name !== '' ? $name : $key,
                'description' => '',
            ]);
        }
    }
}
