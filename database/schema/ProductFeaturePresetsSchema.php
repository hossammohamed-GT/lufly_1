<?php

declare(strict_types=1);

namespace Database\Schema;

use Core\Database\Schema\Blueprint;
use Core\Database\Schema\SchemaDefinition;

/**
 * Ready-made product feature / quick-spec presets for the catalog and admin dashboard.
 * Each preset bundles a canonical key, a default SVG icon key, and localized titles
 * (plus an optional default value) in product_feature_preset_translations.
 */
class ProductFeaturePresetsSchema extends SchemaDefinition
{
    protected string $table = 'product_feature_presets';

    public function define(Blueprint $table): void
    {
        $table->id();
        $table->string('preset_key', 80)->unique();
        $table->string_50('icon_key')->default('spec');
        $table->string_150('category_hint')->nullable()->index();
        $table->integer('sort_order')->default(0);
        $table->boolean('is_active')->default(1)->index();
        $table->timestamps();
    }
}
