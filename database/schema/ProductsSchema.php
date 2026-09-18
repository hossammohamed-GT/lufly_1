<?php

declare(strict_types=1);

namespace Database\Schema;

use Core\Database\Schema\Blueprint;
use Core\Database\Schema\SchemaDefinition;

/**
 * Product catalog - the base product / main model.
 * Created once per model (e.g. Wall Hung Toilet). Everything that can vary
 * per finish or size lives in product_variants; everything translatable
 * lives in product_translations.
 */
class ProductsSchema extends SchemaDefinition
{
    protected string $table = 'products';

    public function define(Blueprint $table): void
    {
        $table->id();
        $table->string_100('model_code')->nullable()->index();
        $table->slug()->unique();
        $table->foreignId('category_id')->nullable()->index();
        $table->foreignId('collection_id')->nullable()->index();
        $table->foreignId('brand_id')->nullable()->index();
        $table->string('status', 50)->default('active')->index(); // draft|active|hidden|discontinued|coming_soon
        $table->boolean('is_featured')->default(0)->index();
        $table->integer('sort_order')->default(0);
        $table->timestamps();
        $table->soft_delete();

        $table->foreign('category_id')->references('id')->on('categories')->onDelete('SET NULL');
        $table->foreign('collection_id')->references('id')->on('collections')->onDelete('SET NULL');
        $table->foreign('brand_id')->references('id')->on('brands')->onDelete('SET NULL');
    }
}
