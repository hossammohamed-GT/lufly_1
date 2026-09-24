<?php

declare(strict_types=1);

namespace Modules\Announcements\Services;

use App\Services\ActivityLogger;
use App\Services\LocalizationService;
use Core\Database\Paginator;
use Core\Exceptions\NotFoundException;
use Modules\Announcements\Models\Announcement;
use Modules\Announcements\Repositories\AnnouncementRepository;

class AnnouncementService
{
    public function __construct(
        private readonly AnnouncementRepository $announcements,
        private readonly LocalizationService $localization,
        private readonly ActivityLogger $activity,
    ) {
    }

    public function activeFor(string $placement = 'topbar', int $limit = 5): array
    {
        return $this->announcements->active($placement, $limit);
    }

    public function paginate(int $page = 1, int $perPage = 15): Paginator
    {
        $connection = Announcement::query()->connection();

        $total = (int) ($connection->selectOne(
            'SELECT COUNT(*) AS aggregate FROM announcements WHERE deleted_at IS NULL',
        )['aggregate'] ?? 0);

        $rows = $connection->select(
            'SELECT * FROM announcements WHERE deleted_at IS NULL ORDER BY sort_order ASC, id ASC LIMIT '
            . $perPage . ' OFFSET ' . (($page - 1) * $perPage),
        );

        $items = array_map(static fn (array $row): Announcement => Announcement::fromRow($row), $rows);

        return new Paginator($items, $total, $page, $perPage);
    }

    public function find(int $id): Announcement
    {
        $announcement = $this->announcements->find($id);
        if ($announcement === null) {
            throw new NotFoundException(trans('errors.not_found'));
        }

        return $announcement;
    }

    public function create(array $data, array $translations): Announcement
    {
        $announcement = $this->announcements->create($data);
        $this->localization->syncTranslations('announcement', $announcement->id, $translations);
        $this->activity->created('announcement', $announcement->id, ['placement' => $announcement->placement]);

        return $announcement;
    }

    public function update(int $id, array $data, array $translations = []): Announcement
    {
        $announcement = $this->find($id);

        $this->announcements->update($id, $data);

        if ($translations !== []) {
            $this->localization->syncTranslations('announcement', $id, $translations);
        }

        $this->activity->updated('announcement', $id, array_intersect_key($data, array_flip(['is_active', 'placement', 'style'])));

        $fresh = $this->announcements->find($id);

        return $fresh ?? $announcement;
    }

    public function toggle(int $id): bool
    {
        $announcement = $this->find($id);
        $next = (int) $announcement->is_active === 1 ? 0 : 1;

        return $this->announcements->update($id, ['is_active' => $next]);
    }

    public function delete(int $id): bool
    {
        $this->activity->deleted('announcement', $id);

        return $this->announcements->delete($id);
    }
}
