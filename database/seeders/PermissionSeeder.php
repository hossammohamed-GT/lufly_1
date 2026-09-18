<?php

declare(strict_types=1);

namespace Database\Seeders;

use Core\Database\Seeding\Seeder;

class PermissionSeeder extends Seeder
{
    private const PERMISSIONS = [
        'products.view' => 'View products',
        'products.manage' => 'Create, edit and delete products',
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

    private const EDITOR_PERMISSIONS = [
        'products.view', 'products.manage', 'announcements.view', 'announcements.manage',
        'media.view', 'media.manage', 'notifications.view',
    ];

    private const VIEWER_PERMISSIONS = [
        'products.view', 'announcements.view', 'media.view', 'notifications.view',
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $key => $name) {
            if (!$this->db->table('permissions')->where('key', $key)->exists()) {
                $this->db->insert('permissions', [
                    'key' => $key,
                    'name' => $name,
                    'description' => $name,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        $this->sync('admin', array_keys(self::PERMISSIONS));
        $this->sync('editor', self::EDITOR_PERMISSIONS);
        $this->sync('viewer', self::VIEWER_PERMISSIONS);
    }

    /** @param string[] $permissionKeys */
    private function sync(string $roleName, array $permissionKeys): void
    {
        $role = $this->db->selectOne('SELECT id FROM roles WHERE name = ?', [$roleName]);
        if ($role === null) {
            return;
        }

        $roleId = (int) $role['id'];
        $this->db->affect('DELETE FROM role_permissions WHERE role_id = ?', [$roleId]);

        foreach ($permissionKeys as $key) {
            $permission = $this->db->selectOne('SELECT id FROM permissions WHERE key = ?', [$key]);
            if ($permission !== null) {
                $this->db->insert('role_permissions', [
                    'role_id' => $roleId,
                    'permission_id' => (int) $permission['id'],
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }
}
