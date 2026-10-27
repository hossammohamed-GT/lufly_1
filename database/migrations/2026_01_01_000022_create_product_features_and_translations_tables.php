<?php

declare(strict_types=1);

namespace Database\Migrations;

use Core\Database\Migration\Migration;
use Database\Schema\ProductCardFeatureTranslationsSchema;
use Database\Schema\ProductCardFeaturesSchema;
use Database\Schema\ProductFeaturePresetTranslationsSchema;
use Database\Schema\ProductFeaturePresetsSchema;
use Database\Schema\ProductSpecificationTranslationsSchema;

/**
 * Adds ready-made product feature presets, per-product 4-6 card features/specs
 * with icon keys and localized labels/values, and localized specification rows.
 */
class CreateProductFeaturesAndTranslationsTables extends Migration
{
    public function up(): void
    {
        $this->schema->createFromDefinition(new ProductFeaturePresetsSchema());
        $this->schema->createFromDefinition(new ProductFeaturePresetTranslationsSchema());
        $this->schema->createFromDefinition(new ProductCardFeaturesSchema());
        $this->schema->createFromDefinition(new ProductCardFeatureTranslationsSchema());
        $this->schema->createFromDefinition(new ProductSpecificationTranslationsSchema());
    }

    public function down(): void
    {
        foreach ([
            'product_specification_translations',
            'product_card_feature_translations',
            'product_card_features',
            'product_feature_preset_translations',
            'product_feature_presets',
        ] as $table) {
            $this->schema->dropIfExists($table);
        }
    }
}
