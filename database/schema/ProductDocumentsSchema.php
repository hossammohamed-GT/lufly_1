<?php

declare(strict_types=1);

namespace Database\Schema;

use Core\Database\Schema\Blueprint;
use Core\Database\Schema\SchemaDefinition;

class ProductDocumentsSchema extends SchemaDefinition
{
    protected string $table = 'product_documents';

    public function define(Blueprint $table): void
    {
        $table->id();
        $table->foreignId('product_id')->index();
        $table->foreignId('variant_id')->nullable()->index();
        $table->foreignId('media_id')->nullable()->index();
        $table->string_50('document_type')->default('catalog');
        $table->string_50('language')->default('en');
        $table->integer('sort_order')->default(0);
        $table->timestamps();

        $table->foreign('product_id')->references('id')->on('products')->onDelete('CASCADE');
        $table->foreign('variant_id')->references('id')->on('product_variants')->onDelete('CASCADE');
        $table->foreign('media_id')->references('id')->on('media')->onDelete('CASCADE');
    }
}
