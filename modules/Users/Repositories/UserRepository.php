<?php

declare(strict_types=1);

namespace Modules\Users\Repositories;

use App\Repositories\Repository;
use Core\Contracts\UserProviderInterface;
use Modules\Users\Models\User;

class UserRepository extends Repository implements UserProviderInterface
{
    protected string $model = User::class;

    protected bool $auditing = true;

    protected string $auditEntity = 'user';

    public function findByEmail(string $email): ?object
    {
        $user = User::query()->where('email', $email)->first();

        return $user;
    }

    public function findById(int|string $id): ?object
    {
        return User::find($id);
    }

    public function permissionsFor(int|string $userId): array
    {
        $rows = User::query()->connection()->select(
            'SELECT DISTINCT p.key FROM permissions p
             INNER JOIN role_permissions rp ON rp.permission_id = p.id
             INNER JOIN user_roles ur ON ur.role_id = rp.role_id
             WHERE ur.user_id = ?',
            [(int) $userId],
        );

        return array_column($rows, 'key');
    }

    public function syncRoles(int|string $userId, array $roleIds): void
    {
        $connection = User::query()->connection();
        $connection->affect('DELETE FROM user_roles WHERE user_id = ?', [(int) $userId]);

        foreach ($roleIds as $roleId) {
            $connection->insert('user_roles', [
                'user_id' => (int) $userId,
                'role_id' => (int) $roleId,
            ]);
        }
    }
}
