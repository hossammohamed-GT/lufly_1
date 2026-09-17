<?php

declare(strict_types=1);

namespace Database\Schema;

use Core\Database\Schema\Blueprint;
use Core\Database\Schema\SchemaDefinition;

class ProductsSchema extends SchemaDefinition
{
    protected string $table = 'products';

    public function define(Blueprint $table): void
    {
        $table->id();
        $table->foreignId('category_id')->nullable();
        $table->string_100('sku')->unique();
        $table->slug()->unique();
        $table->decimal('price', 12, 2)->default(0);
        $table->string_255('image')->nullable();
        $table->json('specs')->nullable();
        $table->status();
        $table->seo();
        $table->timestamps();
        $table->soft_delete();

        $table->foreign('category_id')->references('id')->on('categories')->onDelete('SET NULL');
    }
}
