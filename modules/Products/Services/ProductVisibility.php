<?php

declare(strict_types=1);

namespace Modules\Products\Services;

use Modules\Products\Models\Product;

/**
 * One public-visibility rule for every storefront surface.
 *
 * A product needs a usable product photo AND a usable technical drawing before
 * visitors can open it. This is deliberately evaluated from current media on
 * every request: removing either attachment hides the product immediately and
 * completing them restores an otherwise public product without a data import.
 * Manual/lifecycle statuses are never rewritten and no product is deleted.
 */
final class ProductVisibility
{
    public const PUBLIC_STATUSES = ['active', 'coming_soon'];

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

    public static function isPublic(Product $product): bool
    {
        return in_array((string) $product->status, self::PUBLIC_STATUSES, true)
            && self::media($product)['ready'];
    }

    /** Active is still required for cart/box actions; Coming soon is view-only. */
    public static function isSellable(Product $product): bool
    {
        return (string) $product->status === 'active' && self::media($product)['ready'];
    }

    /**
     * SQL equivalent of media(), for catalog/count/sitemap queries that do not
     * hydrate models. Only a fixed internal alias is accepted.
     */
    public static function mediaReadySql(string $alias = 'p'): string
    {
        if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $alias) !== 1) {
            throw new \InvalidArgumentException('Invalid product table alias.');
        }

        $photo = self::mediaExistsSql($alias, "pm.type NOT IN ('drawing', 'situ')");
        $drawing = self::mediaExistsSql($alias, "pm.type = 'drawing'");

        return "({$photo} AND {$drawing})";
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
}
