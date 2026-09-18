<?php

declare(strict_types=1);

namespace Database\Seeders;

use Core\Database\Seeding\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(LanguageSeeder::class);
        $this->call(RoleSeeder::class);
        $this->call(PermissionSeeder::class);
        $this->call(UserSeeder::class);
        $this->call(SettingSeeder::class);
        $this->call(CategorySeeder::class);
        $this->call(CatalogSeeder::class);
        $this->call(ProductSeeder::class);
        $this->call(AnnouncementSeeder::class);
    }
}
