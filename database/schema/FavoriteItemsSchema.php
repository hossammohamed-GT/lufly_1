<?php

declare(strict_types=1);

namespace Database\Schema;

use Core\Database\Schema\Blueprint;
use Core\Database\Schema\SchemaDefinition;

/**
 * One saved product inside a favorites list.
 *
 * A plain join row: removing a product deletes the row, deleting a product
 * removes it from every list automatically (CASCADE).
 */
class FavoriteItemsSchema extends SchemaDefinition
{
    protected string $table = 'favorite_items';

    public function define(Blueprint $table): void
    {
        $table->id();
        $table->foreignId('favorite_id')->index();
        $table->foreignId('product_id')->index();
        $table->integer('sort_order')->default(0);
        $table->timestamps();

        $table->foreign('favorite_id')->references('id')->on('favorites')->onDelete('CASCADE');
        $table->foreign('product_id')->references('id')->on('products')->onDelete('CASCADE');
        $table->unique('favorite_id', 'product_id');
    }
}
