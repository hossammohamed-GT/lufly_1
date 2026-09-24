<?php

declare(strict_types=1);

namespace Database\Schema;

use Core\Database\Schema\Blueprint;
use Core\Database\Schema\SchemaDefinition;

class ProductSpecificationsSchema extends SchemaDefinition
{
    protected string $table = 'product_specifications';

    public function define(Blueprint $table): void
    {
        $table->id();
        $table->foreignId('product_id')->index();
        $table->foreignId('variant_id')->nullable()->index();
        $table->string_150('spec_key');
        $table->string_255('spec_value');
        $table->string_50('unit')->nullable();
        $table->integer('sort_order')->default(0);
        $table->timestamps();

        $table->foreign('product_id')->references('id')->on('products')->onDelete('CASCADE');
        $table->foreign('variant_id')->references('id')->on('product_variants')->onDelete('CASCADE');
        $table->index('spec_key');
    }
}
