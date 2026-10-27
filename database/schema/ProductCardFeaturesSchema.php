<?php

declare(strict_types=1);

namespace Database\Schema;

use Core\Database\Schema\Blueprint;
use Core\Database\Schema\SchemaDefinition;

/**
 * Per-product compact specification / feature highlights (4 to 6 per product).
 * Supports selecting a ready-made preset (preset_id) OR entering a fully custom
 * feature (preset_id = NULL) with any chosen icon_key from the icon catalog.
 */
class ProductCardFeaturesSchema extends SchemaDefinition
{
    protected string $table = 'product_card_features';

    public function define(Blueprint $table): void
    {
        $table->id();
        $table->foreignId('product_id')->index();
        $table->foreignId('preset_id')->nullable()->index();
        $table->string_50('icon_key')->default('spec');
        $table->integer('sort_order')->default(0);
        $table->timestamps();

        $table->foreign('product_id')->references('id')->on('products')->onDelete('CASCADE');
        $table->foreign('preset_id')->references('id')->on('product_feature_presets')->onDelete('SET NULL');
    }
}
