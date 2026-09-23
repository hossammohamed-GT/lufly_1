<?php

declare(strict_types=1);

namespace Modules\Favorites\Models;

use Core\Database\Model;

/**
 * A saved product inside a list. A plain join row (no soft delete: removing the
 * row is the removal, and deleting a product pulls it out of every list).
 */
class FavoriteItem extends Model
{
    protected static string $table = 'favorite_items';

    protected static bool $softDelete = false;

    protected static array $fillable = ['favorite_id', 'product_id', 'sort_order'];

    protected static array $casts = [
        'id' => 'int',
        'favorite_id' => 'int',
        'product_id' => 'int',
        'sort_order' => 'int',
    ];
}
