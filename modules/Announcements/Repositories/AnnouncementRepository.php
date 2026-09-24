<?php

declare(strict_types=1);

namespace Modules\Announcements\Repositories;

use App\Repositories\Repository;
use Modules\Announcements\Models\Announcement;

class AnnouncementRepository extends Repository
{
    protected string $model = Announcement::class;

    protected bool $auditing = true;

    protected string $auditEntity = 'announcement';

    public function active(string $placement = 'topbar', int $limit = 5): array
    {
        $now = date('Y-m-d H:i:s');

        $rows = Announcement::query()
            ->where('is_active', 1)
            ->where('placement', $placement)
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc')
            ->limit($limit)
            ->get();

        return array_values(array_filter(
            $rows,
            static fn (Announcement $a): bool => $a->isLive(),
        ));
    }
}
