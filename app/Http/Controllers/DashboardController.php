<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Core\Database\DatabaseManager;
use Core\Http\Response;
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

    private function stats(): array
    {
        $stats = [];
        foreach (['users', 'products', 'media', 'languages', 'activity_logs'] as $table) {
            try {
                $stats[$table] = $this->db->connection()->table($table)->count();
            } catch (Throwable) {
                $stats[$table] = 0;
            }
        }

        return $stats;
    }
}
