<?php

declare(strict_types=1);

namespace Modules\Users\Models;

use Core\Database\Model;

class User extends Model
{
    protected static string $table = 'users';

    protected static array $fillable = [
        'name', 'email', 'password', 'phone', 'locale', 'status',
    ];

    protected static array $casts = [
        'id' => 'int',
    ];

    public function toArray(): array
    {
        $attributes = parent::toArray();
        unset($attributes['password']);

        return $attributes;
    }

    public function roleNames(): array
    {
        return array_column(
            static::db()->connection()->select(
                'SELECT r.name FROM roles r
                 INNER JOIN user_roles ur ON ur.role_id = r.id
                 WHERE ur.user_id = ?',
                [$this->getKey()],
            ),
            'name',
        );
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roleNames(), true);
    }
}
