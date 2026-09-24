<?php

declare(strict_types=1);

namespace Database\Schema;

use Core\Database\Schema\Blueprint;
use Core\Database\Schema\SchemaDefinition;

class ProductDimensionsSchema extends SchemaDefinition
{
    protected string $table = 'product_dimensions';

    public function define(Blueprint $table): void
    {
        $table->id();
        $table->foreignId('product_id')->index();
        $table->foreignId('variant_id')->nullable()->index();
        $table->decimal('width_mm', 10, 2)->nullable();
        $table->decimal('height_mm', 10, 2)->nullable();
        $table->decimal('depth_mm', 10, 2)->nullable();
        $table->decimal('weight_kg', 10, 2)->nullable();
        $table->timestamps();

        $table->foreign('product_id')->references('id')->on('products')->onDelete('CASCADE');
        $table->foreign('variant_id')->references('id')->on('product_variants')->onDelete('CASCADE');
        $table->unique('product_id', 'variant_id');
    }
}
