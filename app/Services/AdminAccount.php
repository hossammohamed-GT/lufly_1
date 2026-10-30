<?php

declare(strict_types=1);

namespace App\Services;

use Core\Database\DatabaseManager;

/**
 * The single administrator account, kept in sync with .env.
 *
 * The platform runs with exactly one admin: ADMIN_EMAIL identifies the
 * account in the database, while ADMIN_PASSWORD is supplied by the
 * installation owner through .env and only its bcrypt hash ever reaches
 * the database — the password itself is never printed, logged or rendered
 * anywhere. Every other user account is forced inactive so the account
 * synced here stays the only way into /admin.
 *
 * Two callers share this one implementation: `php cli admin:setup` on
 * machines with a console, and the admin sign-in itself — on hosts without
 * a console (plain shared hosting) the first sign-in whose credentials
 * match .env creates (or resets) the account automatically.
 */
final class AdminAccount
{
    /** Canonical permission keys (kept in sync with Database\Seeders\PermissionSeeder). */
    private const PERMISSIONS = [
        'products.view' => 'View products',
        'products.manage' => 'Create, edit and delete products',
        'categories.view' => 'View categories, collections and brands',
        'categories.manage' => 'Manage categories, collections and brands',
        'announcements.view' => 'View announcements',
        'announcements.manage' => 'Create, edit and delete announcements',
        'users.manage' => 'Manage users',
        'permissions.manage' => 'Manage roles and permissions',
        'languages.manage' => 'Manage languages',
        'media.view' => 'View media library',
        'media.manage' => 'Upload and delete media',
        'settings.manage' => 'Manage platform settings',
        'seo.manage' => 'Manage SEO defaults',
        'notifications.view' => 'View notifications',
    ];

    public function __construct(
        private readonly DatabaseManager $db,
    ) {
    }

    /**
     * Create or reset the one administrator account. The caller guarantees
     * a valid email and a password of at least 14 characters.
     */
    public function sync(string $email, string $password, string $adminName): void
    {
        $connection = $this->db->connection();
        $now = date('Y-m-d H:i:s');
        $displayName = $adminName !== '' ? $adminName : 'LUFLY Administrator';

        $connection->transaction(function () use ($connection, $email, $password, $displayName, $now): void {
            /* The admin role plus every canonical permission. */
            $role = $connection->selectOne('SELECT id FROM roles WHERE name = ?', ['admin']);
            $roleId = $role !== null
                ? (int) $role['id']
                : (int) $connection->table('roles')->insert([
                    'name' => 'admin',
                    'description' => 'Full access to every module.',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

            foreach (self::PERMISSIONS as $key => $label) {
                $permission = $connection->selectOne('SELECT id FROM permissions WHERE `key` = ?', [$key]);
                $permissionId = $permission !== null
                    ? (int) $permission['id']
                    : (int) $connection->table('permissions')->insert([
                        'key' => $key,
                        'name' => $label,
                        'description' => $label,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                $granted = $connection->selectOne(
                    'SELECT id FROM role_permissions WHERE role_id = ? AND permission_id = ?',
                    [$roleId, $permissionId],
                );
                if ($granted === null) {
                    $connection->table('role_permissions')->insert([
                        'role_id' => $roleId,
                        'permission_id' => $permissionId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }

            /* The one and only administrator account. */
            $hash = password_hash($password, PASSWORD_BCRYPT, [
                'cost' => (int) config('security.bcrypt_cost', 12),
            ]);
            $existing = $connection->selectOne('SELECT id FROM users WHERE email = ?', [$email]);
            if ($existing !== null) {
                $userId = (int) $existing['id'];
                $connection->table('users')->where('id', $userId)->update([
                    'name' => $displayName,
                    'password' => $hash,
                    'status' => 'active',
                    'deleted_at' => null,
                    'updated_at' => $now,
                ]);
            } else {
                $userId = (int) $connection->table('users')->insert([
                    'name' => $displayName,
                    'email' => $email,
                    'password' => $hash,
                    'locale' => 'en',
                    'status' => 'active',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            /* Exactly the admin role for this account... */
            $connection->affect('DELETE FROM user_roles WHERE user_id = ? AND role_id != ?', [$userId, $roleId]);
            $hasRole = $connection->selectOne(
                'SELECT id FROM user_roles WHERE user_id = ? AND role_id = ?',
                [$userId, $roleId],
            );
            if ($hasRole === null) {
                $connection->table('user_roles')->insert([
                    'user_id' => $userId,
                    'role_id' => $roleId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            /* ...and no second way in: single-account installations keep every
               other user inactive so an old account can never log in again. */
            $connection->affect(
                "UPDATE users SET status = 'inactive', updated_at = ? WHERE id != ? AND status != 'inactive'",
                [$now, $userId],
            );
        });
    }

    /**
     * Sign-in fallback for hosts without a console: when the submitted
     * credentials exactly match ADMIN_EMAIL/ADMIN_PASSWORD in .env, sync
     * the administrator from .env and return the canonical email so the
     * caller can retry authentication once. Returns null when .env holds
     * no usable admin credentials or the submitted ones differ — the .env
     * password is then never usable as a second key, and clearing
     * ADMIN_PASSWORD after the first sign-in closes this path entirely.
     */
    public function syncFromEnvIfMatches(string $email, string $password): ?string
    {
        $envEmail = strtolower(trim((string) env('ADMIN_EMAIL', '')));
        $envPassword = (string) env('ADMIN_PASSWORD', '');

        if (!filter_var($envEmail, FILTER_VALIDATE_EMAIL) || strlen($envPassword) < 14) {
            return null;
        }
        if (strtolower(trim($email)) !== $envEmail || !hash_equals($envPassword, $password)) {
            return null;
        }

        $this->sync($envEmail, $envPassword, trim((string) env('ADMIN_NAME', 'LUFLY Administrator')));

        return $envEmail;
    }
}
