<?php

declare(strict_types=1);

namespace Database\Seeders;

use Core\Database\Seeding\Seeder;

/**
 * Catalog foundations that are factually verifiable from the legacy system:
 * the LUFLY brand record only.
 *
 * Marketing collections (Signature / Elite / Luxury) and the attribute
 * dictionary were placeholder content and have been removed: the legacy
 * database carries no such records. Collections and attributes are created
 * from Admin once the real data is supplied.
 */
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
