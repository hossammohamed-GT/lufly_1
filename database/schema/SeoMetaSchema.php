<?php

declare(strict_types=1);

namespace Database\Schema;

use Core\Database\Schema\Blueprint;
use Core\Database\Schema\SchemaDefinition;

class SeoMetaSchema extends SchemaDefinition
{
    protected string $table = 'seo_meta';

    public function define(Blueprint $table): void
    {
        $table->id();
        $table->foreignId('product_id')->index();
        $table->string_50('locale')->index();
        $table->string_255('meta_title')->nullable();
        $table->string('meta_description', 500)->nullable();
        $table->string_255('og_title')->nullable();
        $table->string('og_description', 500)->nullable();
        $table->string_255('canonical_url')->nullable();
        $table->timestamps();

        $table->foreign('product_id')->references('id')->on('products')->onDelete('CASCADE');
        $table->unique('product_id', 'locale');
    }
}
