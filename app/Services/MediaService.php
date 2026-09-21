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

    /**
     * @param array<string, mixed> $file $_FILES entry
     */
    public function storeFromUpload(array $file, string $collection = 'general', int|string|null $ownerId = null): Media
    {
        $stored = $this->uploads->store($file, $collection);

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

    /** @return Media[] */
    public function all(?string $collection = null): array
    {
        $query = Media::query()->orderBy('id', 'desc');
        if ($collection !== null) {
            $query->where('collection', $collection);
        }

        /** @var Media[] $items */
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
}
