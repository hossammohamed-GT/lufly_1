<?php

declare(strict_types=1);

namespace Database\Seeders;

use Core\Database\Seeding\Seeder;

class LanguageSeeder extends Seeder
{
    public function run(): void
    {
        $languages = [
            ['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'dir' => 'ltr', 'active' => 1, 'sort_order' => 1],
            ['code' => 'tr', 'name' => 'Turkish', 'native_name' => 'Türkçe', 'dir' => 'ltr', 'active' => 1, 'sort_order' => 2],
            ['code' => 'cs', 'name' => 'Czech', 'native_name' => 'Čeština', 'dir' => 'ltr', 'active' => 1, 'sort_order' => 3],
        ];

        foreach ($languages as $language) {
            if (!$this->db->table('languages')->where('code', $language['code'])->exists()) {
                $this->db->insert('languages', $language + [
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }
}
