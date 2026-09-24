<?php

declare(strict_types=1);

namespace Database\Schema;

use Core\Database\Schema\Blueprint;
use Core\Database\Schema\SchemaDefinition;

class BoxItemsSchema extends SchemaDefinition
{
    protected string $table = 'box_items';

    public function define(Blueprint $table): void
    {
        $table->id();
        $table->foreignId('box_id')->index();
        $table->foreignId('product_id')->index();
        $table->integer('sort_order')->default(0);
        $table->timestamps();

        $table->foreign('box_id')->references('id')->on('boxes')->onDelete('CASCADE');
        $table->foreign('product_id')->references('id')->on('products')->onDelete('CASCADE');
        $table->unique('box_id', 'product_id');
    }
}
