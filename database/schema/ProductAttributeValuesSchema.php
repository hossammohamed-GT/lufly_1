<?php

declare(strict_types=1);

namespace Database\Schema;

use Core\Database\Schema\Blueprint;
use Core\Database\Schema\SchemaDefinition;

/**
 * Attribute values attached to products (or to a single variant when
 * variant_id is set). Either a predefined option or a free-form value.
 * Example: Color = White, Material = Ceramic.
 */
class ProductAttributeValuesSchema extends SchemaDefinition
{
    protected string $table = 'product_attribute_values';

    public function define(Blueprint $table): void
    {
        $table->id();
        $table->foreignId('product_id')->index();
        $table->foreignId('variant_id')->nullable()->index();
        $table->foreignId('attribute_id')->index();
        $table->foreignId('attribute_option_id')->nullable();
        $table->string_255('custom_value')->nullable();

        $table->foreign('product_id')->references('id')->on('products')->onDelete('CASCADE');
        $table->foreign('variant_id')->references('id')->on('product_variants')->onDelete('CASCADE');
        $table->foreign('attribute_id')->references('id')->on('attributes')->onDelete('CASCADE');
        $table->foreign('attribute_option_id')->references('id')->on('attribute_options')->onDelete('CASCADE');
        $table->unique('product_id', 'variant_id', 'attribute_id', 'attribute_option_id');
    }
}
