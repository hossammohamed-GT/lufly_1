<?php

declare(strict_types=1);

namespace Database\Seeders;

use Core\Database\Seeding\Seeder;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        if ($this->db->table('brands')->where('slug', 'lufly')->exists()) {
            return;
        }

        $this->db->insert('brands', [
            'name' => 'LUFLY',
            'slug' => 'lufly',
            'logo' => '/images/logo.png',
            'country' => 'Turkey',
            'website' => 'https://www.lufly.tr',
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
