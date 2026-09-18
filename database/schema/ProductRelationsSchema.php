<?php

declare(strict_types=1);

namespace Database\Schema;

use Core\Database\Schema\Blueprint;
use Core\Database\Schema\SchemaDefinition;

/**
 * Relations between products.
 * Types: accessory | replacement | alternative | spare_part | compatible
 */
class ProductRelationsSchema extends SchemaDefinition
{
    protected string $table = 'product_relations';

    public function define(Blueprint $table): void
    {
        $table->id();
        $table->foreignId('product_id')->index();
        $table->foreignId('related_product_id')->index();
        $table->string_50('relation_type')->default('related');

        $table->foreign('product_id')->references('id')->on('products')->onDelete('CASCADE');
        $table->foreign('related_product_id')->references('id')->on('products')->onDelete('CASCADE');
        $table->unique('product_id', 'related_product_id', 'relation_type');
    }
}
