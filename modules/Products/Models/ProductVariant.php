<?php

declare(strict_types=1);

namespace Modules\Products\Models;

use Core\Database\Model;

/**
 * One sellable copy of a product (finish / size / color).
 */
class ProductVariant extends Model
{
    protected static string $table = 'product_variants';

    protected static array $fillable = [
        'product_id', 'sku', 'variant_name', 'price', 'stock_status',
        'sort_order', 'status',
    ];

    protected static array $casts = [
        'id' => 'int',
        'product_id' => 'int',
        'price' => 'float',
        'sort_order' => 'int',
    ];
}
