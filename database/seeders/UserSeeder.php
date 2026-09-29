<?php

declare(strict_types=1);

namespace Database\Seeders;

use Core\Database\Seeding\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        /* Production-safe by default: an administrator is created only when
           deployment explicitly supplies credentials. This prevents the
           historic admin@lufly.test / password account reaching production. */
        $email = strtolower(trim((string) env('ADMIN_EMAIL', '')));
        $password = (string) env('ADMIN_PASSWORD', '');

        if ($email === '' || $password === '') {
            return;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 14) {
            throw new \RuntimeException('ADMIN_EMAIL must be valid and ADMIN_PASSWORD must contain at least 14 characters.');
        }

        if ($this->db->table('users')->where('email', $email)->exists()) {
            return;
        }

        $userId = $this->db->insert('users', [
            'name' => trim((string) env('ADMIN_NAME', 'LUFLY Administrator')),
            'email' => $email,
            'password' => password_hash($password, PASSWORD_BCRYPT, [
                'cost' => (int) config('security.bcrypt_cost', 12),
            ]),
            'locale' => 'en',
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $role = $this->db->selectOne('SELECT id FROM roles WHERE name = ?', ['admin']);
        if ($role !== null) {
            $this->db->insert('user_roles', [
                'user_id' => (int) $userId,
                'role_id' => (int) $role['id'],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }
}
