<?php

declare(strict_types=1);

namespace Database\Seeders;

use Core\Database\Seeding\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'company_name' => 'LUFLY İNŞAAT SANAYİ VE TİCARET LİMİTED ŞİRKETİ',
            'brand_name' => 'LUFLY Sanitary & Ceramic Ware',
            'contact_email' => 'info@lufly.tr',
            'contact_phone' => '+90 850 3040 817',
            'contact_whatsapp' => '+90 850 3040 817',
            'contact_address' => 'Gaziantep, Turkey',
            'factory_warranty' => '10 Years Factory Guarantee',
            'default_meta_description' => 'LUFLY - Premium Turkish Vitreous China Sanitary Ware, Rimless Wall-Hung Toilets, Luxury Bidets & Architectural Ceramics Manufacturer and European Exporter.',
        ];

        foreach ($settings as $key => $value) {
            if (!$this->db->table('settings')->where('key', $key)->exists()) {
                $this->db->insert('settings', [
                    'key' => $key,
                    'value' => json_encode($value, JSON_UNESCAPED_UNICODE),
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }
}
