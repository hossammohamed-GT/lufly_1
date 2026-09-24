<?php

declare(strict_types=1);

namespace App\Services;

use Core\Exceptions\NotFoundException;
use Modules\Media\Models\Media;

class MediaService
{
    public function __construct(
        private readonly UploadService $uploads,
        private readonly ActivityLogger $activity,
    ) {
    }

    public function storeFromUpload(array $file, string $collection = 'general', int|string|null $ownerId = null): Media
    {
        $cleanCollection = trim(preg_replace('/[^a-zA-Z0-9_\-]+/', '-', strtolower(trim($collection))), '-');
        if ($cleanCollection === '') {
            $cleanCollection = 'general';
        }

        $stored = $this->uploads->store($file, $cleanCollection);

        $media = Media::create([
            'collection' => $stored['directory'],
            'filename' => $stored['filename'],
            'original_name' => $stored['original_name'],
            'path' => $stored['path'],
            'mime_type' => $stored['mime'],
            'extension' => $stored['extension'],
            'size' => $stored['size'],
            'meta' => [],
            'owner_id' => $ownerId === null ? null : (int) $ownerId,
            'status' => 'active',
        ]);

        $this->activity->created('media', $media->id, ['filename' => $stored['filename']]);

        return $media;
    }

    public function find(int $id): Media
    {
        $media = Media::find($id);
        if ($media === null) {
            throw new NotFoundException(trans('errors.media_not_found'));
        }

        return $media;
    }

    public function all(?string $collection = null): array
    {
        $query = Media::query()->orderBy('id', 'desc');
        if ($collection !== null) {
            $query->where('collection', $collection);
        }

        $items = $query->get();

        return $items;
    }

    public function paginate(int $page = 1, int $perPage = 20, ?string $collection = null): \Core\Database\Paginator
    {
        $query = Media::query()->orderBy('id', 'desc');
        if ($collection !== null && $collection !== '') {
            $query->where('collection', $collection);
        }

        return $query->paginate($page, $perPage);
    }

    public function delete(int $id): void
    {
        $media = $this->find($id);
        $this->uploads->delete((string) $media->path);
        $media->delete();

        $this->activity->deleted('media', $id);
    }

    public function collections(): array
    {
        $connection = Media::query()->connection();
        $rows = $connection->select(
            "SELECT m.collection, COUNT(*) as count,
                    (SELECT ct.name FROM categories c
                     LEFT JOIN category_translations ct ON ct.category_id = c.id AND ct.locale = 'en'
                     WHERE c.slug = m.collection LIMIT 1) as category_name
             FROM media m
             WHERE m.collection IS NOT NULL AND m.collection <> ''
             GROUP BY m.collection
             ORDER BY count DESC, m.collection ASC"
        );

        return array_map(static fn (array $row): array => [
            'collection' => (string) $row['collection'],
            'label' => !empty($row['category_name'])
                ? (string) $row['category_name']
                : ucwords(str_replace(['-', '_'], ' ', (string) $row['collection'])),
            'count' => (int) $row['count'],
        ], $rows);
    }
}
