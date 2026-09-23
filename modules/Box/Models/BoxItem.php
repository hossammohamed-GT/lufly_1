<?php

declare(strict_types=1);

namespace Modules\Box\Models;

use Core\Database\Model;

/** One piece inside a box. */
class BoxItem extends Model
{
    protected static string $table = 'box_items';

    /* A plain join row: taking the piece out deletes the row, and the unique
       (box_id, product_id) — not a deleted_at that does not exist here — is what
       keeps a piece from being counted twice. */
    protected static bool $softDelete = false;

    protected static array $fillable = ['box_id', 'product_id', 'sort_order'];

    protected static array $casts = [
        'id' => 'int',
        'box_id' => 'int',
        'product_id' => 'int',
        'sort_order' => 'int',
    ];
}
