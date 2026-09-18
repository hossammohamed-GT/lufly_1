<?php

declare(strict_types=1);

namespace Database\Schema;

use Core\Database\Schema\Blueprint;
use Core\Database\Schema\SchemaDefinition;

/**
 * Media attached to products or variants. The same media library row can be
 * reused in many places.
 * Types: thumbnail | gallery | technical_drawing | lifestyle | installation | video
 */
class ProductMediaSchema extends SchemaDefinition
{
    protected string $table = 'product_media';

    public function define(Blueprint $table): void
    {
        $table->id();
        $table->foreignId('product_id')->index();
        $table->foreignId('variant_id')->nullable()->index();
        $table->foreignId('media_id')->index();
        $table->string_50('type')->default('gallery');
        $table->integer('sort_order')->default(0);
        $table->boolean('is_primary')->default(0);
        $table->timestamps();

        $table->foreign('product_id')->references('id')->on('products')->onDelete('CASCADE');
        $table->foreign('variant_id')->references('id')->on('product_variants')->onDelete('CASCADE');
        $table->foreign('media_id')->references('id')->on('media')->onDelete('CASCADE');
        $table->unique('product_id', 'variant_id', 'media_id', 'type');
    }
}
