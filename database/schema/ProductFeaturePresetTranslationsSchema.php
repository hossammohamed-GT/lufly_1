<?php

declare(strict_types=1);

namespace Database\Schema;

use Core\Database\Schema\Blueprint;
use Core\Database\Schema\SchemaDefinition;

class ProductFeaturePresetTranslationsSchema extends SchemaDefinition
{
    protected string $table = 'product_feature_preset_translations';

    public function define(Blueprint $table): void
    {
        $table->id();
        $table->foreignId('preset_id')->index();
        $table->string('locale', 10)->index();
        $table->string('label', 120);
        $table->string('placeholder_value', 160)->nullable();

        $table->foreign('preset_id')->references('id')->on('product_feature_presets')->onDelete('CASCADE');
        $table->unique('preset_id', 'locale');
    }
}
