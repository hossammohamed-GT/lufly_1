<?php

declare(strict_types=1);

namespace Core\Console\Commands;

use Core\Console\Command;
use Core\Database\DatabaseManager;

/**
 * Create or reset THE single administrator account.
 *
 * The platform runs with exactly one admin: the email identifies the account
 * in the database, while the password is supplied by the installation owner
 * through .env (ADMIN_PASSWORD) — or a one-off --password option — and only
 * its bcrypt hash ever reaches the database. The password itself is never
 * printed, logged or rendered anywhere. Every other user account is forced
 * inactive so the account created here stays the only way into /admin.
 */
final class AdminSetupCommand extends Command
{
    protected string $name = 'admin:setup';

    protected string $description = 'Create/reset the single administrator account from ADMIN_EMAIL/ADMIN_PASSWORD in .env.';

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

    public function handle(array $args, array $options): int
    {
        $email = strtolower(trim((string) ($this->option($options, 'email') ?: env('ADMIN_EMAIL', ''))));
        $password = (string) ($this->option($options, 'password') ?: env('ADMIN_PASSWORD', ''));
        $adminName = trim((string) ($this->option($options, 'name') ?: env('ADMIN_NAME', 'LUFLY Administrator')));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('A valid admin email is required. Set ADMIN_EMAIL in .env or pass --email=you@example.com.');

            return 1;
        }
        if (strlen($password) < 14) {
            $this->error('The admin password must contain at least 14 characters. Set ADMIN_PASSWORD in .env or pass --password=...');

            return 1;
        }

        $connection = $this->app->get(DatabaseManager::class)->connection();
        $now = date('Y-m-d H:i:s');

        $connection->transaction(function () use ($connection, $email, $password, $adminName, $now): void {
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
                    'name' => $adminName !== '' ? $adminName : 'LUFLY Administrator',
                    'password' => $hash,
                    'status' => 'active',
                    'deleted_at' => null,
                    'updated_at' => $now,
                ]);
            } else {
                $userId = (int) $connection->table('users')->insert([
                    'name' => $adminName !== '' ? $adminName : 'LUFLY Administrator',
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

        $this->success("Single administrator ready: {$email}");
        $this->line('Every other user account is now inactive.');
        $this->line('Tip: remove ADMIN_PASSWORD from .env now — only its bcrypt hash is stored.');

        return 0;
    }
}
