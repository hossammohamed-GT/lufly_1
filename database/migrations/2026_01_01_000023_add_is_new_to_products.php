<?php

declare(strict_types=1);

namespace Database\Migrations;

use Core\Database\Migration\Migration;

/**
 * Adds the manually-set "New" flag to products. It is independent of
 * is_featured: the storefront shows a NEW pill next to the featured badge,
 * and the admin flips it straight from the product card.
 */
class AddIsNewToProducts extends Migration
{
    public function up(): void
    {
        if ($this->hasColumn('products', 'is_new')) {
            return;
        }

        $this->db->query('ALTER TABLE products ADD COLUMN is_new TINYINT(1) NOT NULL DEFAULT 0');

        try {
            $this->db->query('CREATE INDEX products_is_new_index ON products (is_new)');
        } catch (\Throwable) {
            /* the index is a filter accelerator only — never block the column */
        }
    }

    public function down(): void
    {
        if (!$this->hasColumn('products', 'is_new')) {
            return;
        }

        $this->db->query('ALTER TABLE products DROP COLUMN is_new');
    }

    private function hasColumn(string $table, string $column): bool
    {
        if ($this->db->driver() === 'sqlite') {
            foreach ($this->db->select("PRAGMA table_info({$table})") as $row) {
                if ((string) ($row['name'] ?? '') === $column) {
                    return true;
                }
            }

            return false;
        }

        $row = $this->db->selectOne(
            'SELECT COUNT(*) AS total FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$table, $column],
        );

        return (int) ($row['total'] ?? 0) > 0;
    }
}
