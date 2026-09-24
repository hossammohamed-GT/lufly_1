<?php

declare(strict_types=1);

namespace Database\Schema;

use Core\Database\Schema\Blueprint;
use Core\Database\Schema\SchemaDefinition;

class ProductSearchKeywordsSchema extends SchemaDefinition
{
    protected string $table = 'product_search_keywords';

    public function define(Blueprint $table): void
    {
        $table->id();
        $table->foreignId('product_id')->index();
        $table->string_150('keyword');

        $table->foreign('product_id')->references('id')->on('products')->onDelete('CASCADE');
        $table->index('keyword');
        $table->unique('product_id', 'keyword');
    }
}
