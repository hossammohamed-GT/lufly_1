<?php

declare(strict_types=1);

namespace Modules\Permissions\Models;

use Core\Database\Model;

class Role extends Model
{
    protected static string $table = 'roles';

    protected static array $fillable = ['name', 'description'];

    public function permissionKeys(): array
    {
        return array_column(
            static::db()->connection()->select(
                'SELECT p.key FROM permissions p
                 INNER JOIN role_permissions rp ON rp.permission_id = p.id
                 WHERE rp.role_id = ?',
                [$this->getKey()],
            ),
            'key',
        );
    }
}
