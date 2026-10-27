<?php

declare(strict_types=1);

namespace Modules\Products\Services;

use Modules\Products\Models\Product;

/**
 * One public-visibility rule for every storefront surface.
 *
 * A standalone product needs a usable product photo AND a usable technical
 * drawing before visitors can open it. Toilet Seat Covers linked to WCs
 * (`product_relations.relation_type IN ('seat_cover', 'seat_cover_only')`)
 * never appear as standalone cards/pages in catalog grids, search, home,
 * category counts, or sitemaps — instead they appear inside their parent WC's
 * product detail page and remain addable to the Quotation Box / Favorites.
 */
final class ProductVisibility
{
    public const PUBLIC_STATUSES = ['active', 'coming_soon'];

    public const SEAT_COVER_RELATIONS = ['seat_cover', 'seat_cover_only'];

    /** @var array<int, bool>|null product_id => true when product is a WC seat cover */
    private static ?array $seatCoverIds = null;

    /** @return array{photo: bool, drawing: bool, ready: bool} */
    public static function media(Product $product): array
    {
        $photo = false;
        $drawing = false;

        /* Keep the explicit status check here as well as in Product::media():
           it makes the public contract independent of a caller's hydration
           choices and exactly matches mediaReadySql() below. */
        foreach ($product->media() as $row) {
            if (in_array((string) ($row['status'] ?? ''), ['missing', 'hidden'], true)) {
                continue;
            }

            $type = (string) ($row['type'] ?? '');
            if ($type === 'drawing') {
                $drawing = true;
            } elseif ($type !== 'situ') {
                $photo = true;
            }

            if ($photo && $drawing) {
                break;
            }
        }

        return ['photo' => $photo, 'drawing' => $drawing, 'ready' => $photo && $drawing];
    }

    /**
     * Whether this product is a Toilet Seat Cover (attached to a WC rather
     * than listed as a standalone product card).
     */
    public static function isSeatCover(Product $product): bool
    {
        $id = (int) ($product->getKey() ?? 0);
        if ($id <= 0) {
            return false;
        }

        return isset(self::seatCoverMap()[$id]);
    }

    /**
     * Resolve the primary public parent WC slug for a linked Toilet Seat Cover,
     * if an active media-ready WC exists.
     */
    public static function parentWcSlug(int $seatProductId): ?string
    {
        if ($seatProductId <= 0) {
            return null;
        }

        $row = Product::query()->connection()->selectOne(
            "SELECT p.slug
             FROM product_relations pr
             INNER JOIN products p ON p.id = pr.product_id
             WHERE pr.related_product_id = ?
               AND pr.relation_type = 'seat_cover'
               AND p.deleted_at IS NULL
               AND p.status IN ('active', 'coming_soon')
               AND " . self::rawMediaReadySql('p') . "
             ORDER BY p.is_featured DESC, p.id ASC
             LIMIT 1",
            [$seatProductId],
        );

        $slug = trim((string) ($row['slug'] ?? ''));

        return $slug !== '' ? $slug : null;
    }

    public static function isPublic(Product $product): bool
    {
        return in_array((string) $product->status, self::PUBLIC_STATUSES, true)
            && !self::isSeatCover($product)
            && self::media($product)['ready'];
    }

    /**
     * Active is required for quotation box / favorite actions.
     * Linked Toilet Seat Covers remain addable alongside their WC as long as
     * they are active and carry a product photo.
     */
    public static function isSellable(Product $product): bool
    {
        if ((string) $product->status !== 'active') {
            return false;
        }

        $media = self::media($product);
        if (self::isSeatCover($product)) {
            return $media['photo'];
        }

        return $media['ready'];
    }

    /**
     * SQL equivalent of isPublic() media + standalone check for catalog/count/sitemap
     * queries that do not hydrate models. Only a fixed internal alias is accepted.
     */
    public static function mediaReadySql(string $alias = 'p'): string
    {
        $mediaReady = self::rawMediaReadySql($alias);
        $notSeatCover = self::notSeatCoverSql($alias);

        return "({$mediaReady} AND {$notSeatCover})";
    }

    public static function notSeatCoverSql(string $alias = 'p'): string
    {
        self::assertAlias($alias);

        return "NOT EXISTS (
            SELECT 1 FROM product_relations pr_sc
            WHERE pr_sc.related_product_id = {$alias}.id
              AND pr_sc.relation_type IN ('seat_cover', 'seat_cover_only')
        )";
    }

    private static function rawMediaReadySql(string $alias = 'p'): string
    {
        self::assertAlias($alias);

        $photo = self::mediaExistsSql($alias, "pm.type NOT IN ('drawing', 'situ')");
        $drawing = self::mediaExistsSql($alias, "pm.type = 'drawing'");

        return "({$photo} AND {$drawing})";
    }

    private static function assertAlias(string $alias): void
    {
        if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $alias) !== 1) {
            throw new \InvalidArgumentException('Invalid product table alias.');
        }
    }

    private static function mediaExistsSql(string $alias, string $typeRule): string
    {
        return "EXISTS (
            SELECT 1 FROM product_media pm
            INNER JOIN media m ON m.id = pm.media_id
            WHERE pm.product_id = {$alias}.id
              AND pm.variant_id IS NULL
              AND {$typeRule}
              AND m.status NOT IN ('missing', 'hidden')
        )";
    }

    /** @return array<int, bool> */
    private static function seatCoverMap(): array
    {
        if (self::$seatCoverIds !== null) {
            return self::$seatCoverIds;
        }

        $rows = Product::query()->connection()->select(
            "SELECT DISTINCT related_product_id
             FROM product_relations
             WHERE relation_type IN ('seat_cover', 'seat_cover_only')",
        );

        $map = [];
        foreach ($rows as $row) {
            $id = (int) ($row['related_product_id'] ?? 0);
            if ($id > 0) {
                $map[$id] = true;
            }
        }

        return self::$seatCoverIds = $map;
    }

    public static function clearCache(): void
    {
        self::$seatCoverIds = null;
    }
}
