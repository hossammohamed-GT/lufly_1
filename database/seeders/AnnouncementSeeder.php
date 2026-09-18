<?php

declare(strict_types=1);

namespace Database\Seeders;

use Core\Database\Seeding\Seeder;

/**
 * Seeds one live storefront announcement ("the word") so the announcement
 * bar has something to show from the very first deploy. Admins edit or
 * replace it from Admin > Announcements, in every language.
 */
class AnnouncementSeeder extends Seeder
{
    public function run(): void
    {
        if ($this->db->table('announcements')->exists()) {
            return;
        }

        $announcementId = (int) $this->db->insert('announcements', [
            'placement' => 'topbar',
            'style' => 'promo',
            'link_url' => null,
            'is_active' => 1,
            'starts_at' => null,
            'ends_at' => null,
            'sort_order' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $messages = [
            'en' => ['message' => '50% discount today on selected wall-hung toilets', 'cta_label' => 'Shop now'],
            'tr' => ['message' => 'Seçili asma klozetlerde bugün %50 indirim', 'cta_label' => 'Hemen keşfet'],
            'cs' => ['message' => 'Dnes sleva 50 % na vybrané závěsné WC', 'cta_label' => 'Prohlédnout'],
        ];

        foreach ($messages as $locale => $fields) {
            $this->db->insert('announcement_translations', [
                'announcement_id' => $announcementId,
                'locale' => $locale,
                'message' => $fields['message'],
                'cta_label' => $fields['cta_label'],
            ]);
        }
    }
}
