<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Core\Database\DatabaseManager;
use Core\Http\Response;
use Modules\Products\Services\ProductVisibility;
use Throwable;

class DashboardController extends Controller
{
    public function __construct(private readonly DatabaseManager $db)
    {
    }

    public function index(): Response
    {
        return $this->view('admin.dashboard', [
            'title' => trans('common.dashboard'),
            'stats' => $this->stats(),
        ]);
    }

    /** @return array<string, int> */
    private function stats(): array
    {
        $connection = $this->db->connection();
        $counts = [
            'products' => "SELECT COUNT(*) AS total FROM products WHERE deleted_at IS NULL",
            'featured' => "SELECT COUNT(*) AS total FROM products WHERE deleted_at IS NULL AND is_featured = 1",
            'categories' => "SELECT COUNT(*) AS total FROM categories WHERE deleted_at IS NULL",
            'leaf_categories' => "SELECT COUNT(*) AS total FROM categories c
                WHERE c.deleted_at IS NULL
                  AND NOT EXISTS (SELECT 1 FROM categories ch WHERE ch.parent_id = c.id AND ch.deleted_at IS NULL)",
            'collections' => "SELECT COUNT(*) AS total FROM collections WHERE deleted_at IS NULL",
            'brands' => "SELECT COUNT(*) AS total FROM brands WHERE deleted_at IS NULL",
            'media_incomplete' => "SELECT COUNT(*) AS total FROM products p
                WHERE p.deleted_at IS NULL AND p.status IN ('active', 'coming_soon')
                  AND NOT (" . ProductVisibility::mediaReadySql('p') . ')',
        ];

        $stats = [];
        foreach ($counts as $key => $sql) {
            try {
                $stats[$key] = (int) ($connection->selectOne($sql)['total'] ?? 0);
            } catch (Throwable) {
                $stats[$key] = 0;
            }
        }

        return $stats;
    }
}
