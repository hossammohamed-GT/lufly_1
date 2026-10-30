<?php

declare(strict_types=1);

namespace Database\Migrations;

use Core\Database\Migration\Migration;

/**
 * Seeds the three classic marketing collections (Signature / Elite / Luxury)
 * so the Collections admin page — and the product editor's collection
 * picker — are never empty on a fresh database. Runs only while the
 * collections table has no live rows; every name can be renamed or deleted
 * from Admin afterwards.
 */
class SeedDefaultCollections extends Migration
{
    /** @var array<int, array{slug: string, names: array<string, string>}> */
    private const COLLECTIONS = [
        ['slug' => 'signature', 'names' => ['en' => 'Signature', 'tr' => 'Signature', 'cs' => 'Signature']],
        ['slug' => 'elite', 'names' => ['en' => 'Elite', 'tr' => 'Elite', 'cs' => 'Elite']],
        ['slug' => 'luxury', 'names' => ['en' => 'Luxury', 'tr' => 'Lüks', 'cs' => 'Luxury']],
    ];

    public function up(): void
    {
        $existing = $this->db->selectOne(
            'SELECT COUNT(*) AS total FROM collections WHERE deleted_at IS NULL',
        );
        if ((int) ($existing['total'] ?? 0) > 0) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $sort = 0;
        foreach (self::COLLECTIONS as $collection) {
            $bySlug = $this->db->selectOne(
                'SELECT id FROM collections WHERE slug = ? LIMIT 1',
                [$collection['slug']],
            );
            if ($bySlug !== null) {
                continue;
            }

            $sort++;
            $id = (int) $this->db->table('collections')->insert([
                'slug' => $collection['slug'],
                'image' => null,
                'status' => 'active',
                'sort_order' => $sort,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($collection['names'] as $locale => $name) {
                $this->db->table('collection_translations')->insert([
                    'collection_id' => $id,
                    'locale' => $locale,
                    'name' => $name,
                    'description' => null,
                ]);
            }
        }
    }

    public function down(): void
    {
        $slugs = array_column(self::COLLECTIONS, 'slug');
        $placeholders = implode(',', array_fill(0, count($slugs), '?'));
        $rows = $this->db->select(
            "SELECT id FROM collections WHERE slug IN ({$placeholders})",
            $slugs,
        );
        $ids = array_map(static fn (array $row): int => (int) $row['id'], $rows);
        if ($ids === []) {
            return;
        }

        $idPlaceholders = implode(',', array_fill(0, count($ids), '?'));
        $this->db->query(
            "DELETE FROM collection_translations WHERE collection_id IN ({$idPlaceholders})",
            $ids,
        );
        $this->db->query(
            "DELETE FROM collections WHERE id IN ({$idPlaceholders})",
            $ids,
        );
    }
}
