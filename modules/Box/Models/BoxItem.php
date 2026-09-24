<?php

declare(strict_types=1);

namespace Modules\Box\Models;

use Core\Database\Model;

class BoxItem extends Model
{
    protected static string $table = 'box_items';

    protected static bool $softDelete = false;

    protected static array $fillable = ['box_id', 'product_id', 'sort_order'];

    protected static array $casts = [
        'id' => 'int',
        'box_id' => 'int',
        'product_id' => 'int',
        'sort_order' => 'int',
    ];
}
