<?php

declare(strict_types=1);

namespace Database\Schema;

use Core\Database\Schema\Blueprint;
use Core\Database\Schema\SchemaDefinition;

class ProductVariantsSchema extends SchemaDefinition
{
    protected string $table = 'product_variants';

    public function define(Blueprint $table): void
    {
        $table->id();
        $table->foreignId('product_id')->index();
        $table->string_100('sku')->unique();
        $table->string_150('variant_name')->nullable();
        $table->decimal('price', 12, 2)->default(0);
        $table->string_50('stock_status')->default('in_stock')->index(); // in_stock|out_of_stock|pre_order|made_to_order
        $table->integer('sort_order')->default(0);
        $table->string('status', 50)->default('active')->index();
        $table->timestamps();
        $table->soft_delete();

        $table->foreign('product_id')->references('id')->on('products')->onDelete('CASCADE');
    }
}
