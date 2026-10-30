<?php

declare(strict_types=1);

namespace Modules\Products\Services;

use App\Services\ActivityLogger;
use App\Services\MediaService;
use Core\Exceptions\NotFoundException;
use Core\Exceptions\ValidationException;
use Modules\Products\Models\Product;

/**
 * Keeps `product_media` organised in the three sections the catalogue uses:
 *
 *   photos   -> type "main" (exactly one per product) + type "gallery"
 *   drawings -> type "drawing"
 *   situ     -> type "situ"   (product photographed in place)
 *
 * The storefront turns those rows into the tabs of the product page and into
 * the card images, so the invariants below are enforced on every write:
 *   - at most one is_primary row per product, and only inside `photos`;
 *   - the first photo is always type "main" and flagged primary;
 *   - products that only have drawings keep no primary row (their cover comes
 *     from Product::primaryImageUrl()).
 */
class ProductMediaService
{
    /** Section => the media type new uploads get in it. */
    public const SECTION_TYPES = [
        'photos' => 'gallery',
        'drawings' => 'drawing',
        'situ' => 'situ',
    ];

    public function __construct(
        private readonly MediaService $media,
        private readonly ActivityLogger $activity,
    ) {
    }

    /** @return string[] */
    public static function sections(): array
    {
        return array_keys(self::SECTION_TYPES);
    }

    /**
     * Attachments of a product grouped per section, in display order.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function grouped(Product $product): array
    {
        return $product->mediaBySection();
    }

    /**
     * Store one uploaded file straight into a section. With $asPrimary the new
     * image also becomes the product's main photo (legacy "replace image"
     * behaviour of the product form).
     *
     * @param array<string, mixed> $file $_FILES entry
     */
    public function upload(int $productId, array $file, string $section, bool $asPrimary = false): int
    {
        $type = $this->sectionType($section);
        $this->product($productId);

        $media = $this->media->storeFromUpload($file, $this->collection($productId), auth()->id());
        $attachmentId = $this->attach((int) $media->id, $productId, $type);

        if ($asPrimary && $attachmentId > 0 && $this->sectionOf($type) === 'photos') {
            $this->makePrimary($attachmentId);
        }

        return $attachmentId;
    }

    /**
     * Attach an existing media-library file to a product (idempotent: the same
     * file is never linked twice inside the same section).
     *
     * @return int the product_media row id (0 when it could not be attached)
     */
    public function attach(int $mediaId, int $productId, string $type): int
    {
        $connection = Product::query()->connection();

        $existing = $connection->selectOne(
            'SELECT id FROM product_media WHERE product_id = ? AND media_id = ? AND type = ? LIMIT 1',
            [$productId, $mediaId, $type],
        );

        if ($existing !== null) {
            return (int) $existing['id'];
        }

        $attachmentId = (int) $connection->insert('product_media', [
            'product_id' => $productId,
            'variant_id' => null,
            'media_id' => $mediaId,
            'type' => $type,
            'sort_order' => $this->nextSortOrder($productId, $this->sectionOf($type)),
            'is_primary' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->normalise($productId);
        $this->activity->updated('product', $productId, ['media' => $mediaId, 'type' => $type]);

        return $attachmentId;
    }

    /** Move an attachment into another section (photos / drawings / situ). */
    public function move(int $attachmentId, string $section): void
    {
        $type = $this->sectionType($section);
        $row = $this->attachment($attachmentId);

        if ((string) $row['type'] === $type) {
            return;
        }

        $connection = Product::query()->connection();
        $connection->table('product_media')->where('id', $attachmentId)->update([
            'type' => $type,
            'is_primary' => 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->normalise((int) $row['product_id']);
        $this->activity->updated('product', (int) $row['product_id'], [
            'media' => (int) $row['media_id'],
            'moved_to' => $section,
        ]);
    }

    /** Flag one photo as the product's main image. */
    public function makePrimary(int $attachmentId): void
    {
        $row = $this->attachment($attachmentId);
        $productId = (int) $row['product_id'];

        if ($this->sectionOf((string) $row['type']) !== 'photos') {
            throw new ValidationException([
                'section' => [trans('products.media_primary_photos_only')],
            ]);
        }

        $connection = Product::query()->connection();

        /* clear first: the product may only ever carry one primary row */
        $connection->affect(
            'UPDATE product_media SET is_primary = 0 WHERE product_id = ?',
            [$productId],
        );

        $connection->table('product_media')->where('id', $attachmentId)->update([
            'type' => 'main',
            'is_primary' => 1,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->normalise((int) $row['product_id']);
        $this->activity->updated('product', (int) $row['product_id'], ['primary_media' => (int) $row['media_id']]);
    }

    /** Move an attachment one position up or down inside its section. */
    public function reorder(int $attachmentId, string $direction): void
    {
        $row = $this->attachment($attachmentId);
        $productId = (int) $row['product_id'];
        $section = $this->sectionOf((string) $row['type']);

        $ids = array_map(
            static fn (array $item): int => (int) $item['id'],
            $this->sectionRows($productId, $section),
        );

        $index = array_search($attachmentId, $ids, true);
        if ($index === false) {
            return;
        }

        $target = $direction === 'up' ? $index - 1 : $index + 1;
        if ($target < 0 || $target >= count($ids)) {
            return;
        }

        [$ids[$index], $ids[$target]] = [$ids[$target], $ids[$index]];

        $connection = Product::query()->connection();
        foreach ($ids as $position => $id) {
            $connection->table('product_media')->where('id', $id)->update([
                'sort_order' => $position + 1,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $this->normalise($productId);
    }

    /**
     * Detach an image from the product. When nothing else references the
     * file, it is moved to images/deleted-images/ (named with the product
     * code) instead of lingering anonymously in the live folders.
     */
    public function remove(int $attachmentId): void
    {
        $row = $this->attachment($attachmentId);
        $productId = (int) $row['product_id'];

        Product::query()->connection()->table('product_media')->where('id', $attachmentId)->delete();

        $this->normalise($productId);
        $this->trashIfOrphan((int) $row['media_id'], $productId);
        $this->activity->updated('product', $productId, ['detached_media' => (int) $row['media_id']]);
    }

    /**
     * Replace the file behind one attachment, keeping its section, position
     * and primary flag. The old file is retired to deleted-images/ when no
     * other product still uses it.
     *
     * @param array<string, mixed> $file $_FILES entry
     * @return array<string, mixed> attachment payload for the UI
     */
    public function replace(int $attachmentId, array $file): array
    {
        $row = $this->attachment($attachmentId);
        $productId = (int) $row['product_id'];
        $oldMediaId = (int) $row['media_id'];

        $media = $this->media->storeFromUpload($file, $this->collection($productId), auth()->id());

        Product::query()->connection()->table('product_media')->where('id', $attachmentId)->update([
            'media_id' => (int) $media->id,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->normalise($productId);
        if ((int) $media->id !== $oldMediaId) {
            $this->trashIfOrphan($oldMediaId, $productId);
        }
        $this->activity->updated('product', $productId, ['replaced_media' => $oldMediaId, 'media' => (int) $media->id]);

        return $this->attachmentPayload($attachmentId);
    }

    /**
     * Attachment + media data the admin image UI renders from.
     *
     * @return array<string, mixed>
     */
    public function attachmentPayload(int $attachmentId): array
    {
        $row = Product::query()->connection()->selectOne(
            'SELECT pm.id, pm.product_id, pm.media_id, pm.type, pm.sort_order, pm.is_primary, m.path AS media_path
             FROM product_media pm
             JOIN media m ON m.id = pm.media_id
             WHERE pm.id = ? LIMIT 1',
            [$attachmentId],
        );
        if ($row === null) {
            throw new NotFoundException(trans('errors.media_not_found'));
        }

        return [
            'attachment_id' => (int) $row['id'],
            'product_id' => (int) $row['product_id'],
            'media_id' => (int) $row['media_id'],
            'type' => (string) $row['type'],
            'section' => $this->sectionOf((string) $row['type']),
            'is_primary' => (int) $row['is_primary'] === 1,
            'path' => (string) $row['media_path'],
        ];
    }

    /* ---------------------------------------------------------------- internals */

    /**
     * Retire a media file once no product image or datasheet references it.
     * The file lands in images/deleted-images/ named with the product code.
     */
    private function trashIfOrphan(int $mediaId, int $productId): void
    {
        if ($mediaId <= 0) {
            return;
        }

        $connection = Product::query()->connection();
        $attachments = $connection->selectOne(
            'SELECT COUNT(*) AS total FROM product_media WHERE media_id = ?',
            [$mediaId],
        );
        if ((int) ($attachments['total'] ?? 0) > 0) {
            return;
        }
        $documents = $connection->selectOne(
            'SELECT COUNT(*) AS total FROM product_documents WHERE media_id = ?',
            [$mediaId],
        );
        if ((int) ($documents['total'] ?? 0) > 0) {
            return;
        }

        $product = $connection->selectOne('SELECT model_code FROM products WHERE id = ? LIMIT 1', [$productId]);
        $label = trim((string) ($product['model_code'] ?? ''));
        $this->media->trash($mediaId, $label !== '' ? $label : 'product-' . $productId);
    }

    private function product(int $productId): Product
    {
        /** @var Product|null $product */
        $product = Product::find($productId);
        if ($product === null) {
            throw new NotFoundException(trans('errors.product_not_found'));
        }

        return $product;
    }

    /** @return array<string, mixed> */
    private function attachment(int $attachmentId): array
    {
        $row = Product::query()->connection()->selectOne(
            'SELECT id, product_id, media_id, type FROM product_media WHERE id = ? LIMIT 1',
            [$attachmentId],
        );

        if ($row === null) {
            throw new NotFoundException(trans('errors.media_not_found'));
        }

        return $row;
    }

    private function sectionType(string $section): string
    {
        if (!array_key_exists($section, self::SECTION_TYPES)) {
            throw new ValidationException([
                'section' => [trans('products.media_section_invalid')],
            ]);
        }

        return self::SECTION_TYPES[$section];
    }

    private function sectionOf(string $type): string
    {
        return match ($type) {
            'drawing' => 'drawings',
            'situ' => 'situ',
            default => 'photos',
        };
    }

    /** @return array<int, array<string, mixed>> */
    private function sectionRows(int $productId, string $section): array
    {
        $sql = 'SELECT id, type, sort_order FROM product_media
                WHERE product_id = ? AND variant_id IS NULL';
        $bindings = [$productId];

        $sql .= match ($section) {
            'drawings' => " AND type = 'drawing'",
            'situ' => " AND type = 'situ'",
            default => " AND type IN ('main', 'gallery')",
        };
        $sql .= ' ORDER BY sort_order ASC, id ASC';

        return Product::query()->connection()->select($sql, $bindings);
    }

    private function nextSortOrder(int $productId, string $section): int
    {
        $rows = $this->sectionRows($productId, $section);

        return count($rows) + 1;
    }

    /**
     * Re-apply the section invariants and renumber every section so the order
     * stays dense (1..n) after moves, uploads and deletions.
     */
    private function normalise(int $productId): void
    {
        $connection = Product::query()->connection();
        $rows = $connection->select(
            "SELECT id, media_id, type, sort_order, is_primary FROM product_media
             WHERE product_id = ? AND variant_id IS NULL
             ORDER BY is_primary DESC, sort_order ASC, id ASC",
            [$productId],
        );

        $photos = array_values(array_filter(
            $rows,
            fn (array $row): bool => $this->sectionOf((string) $row['type']) === 'photos',
        ));

        $primaryId = null;
        foreach ($photos as $row) {
            if ((int) $row['is_primary'] === 1) {
                $primaryId = (int) $row['id'];
                break;
            }
        }
        $primaryId ??= $photos === [] ? null : (int) $photos[0]['id'];

        $positions = ['photos' => 0, 'drawings' => 0, 'situ' => 0];

        foreach ($rows as $row) {
            $section = $this->sectionOf((string) $row['type']);
            $positions[$section]++;
            $id = (int) $row['id'];
            $isPrimary = $section === 'photos' && $id === $primaryId;

            $type = match ($section) {
                'photos' => $isPrimary ? 'main' : 'gallery',
                'drawings' => 'drawing',
                default => 'situ',
            };

            $connection->table('product_media')->where('id', $id)->update([
                'type' => $type,
                'sort_order' => $positions[$section],
                'is_primary' => $isPrimary ? 1 : 0,
            ]);
        }
    }

    /** Uploads of a product land in its category collection, like the seed data. */
    private function collection(int $productId): string
    {
        $row = Product::query()->connection()->selectOne(
            'SELECT c.slug AS category_slug FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.id = ? LIMIT 1',
            [$productId],
        );

        $slug = trim((string) ($row['category_slug'] ?? ''));

        return $slug !== '' ? $slug : 'products';
    }
}
