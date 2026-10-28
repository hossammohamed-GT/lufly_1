<?php

declare(strict_types=1);

namespace Database\Schema;

use Core\Database\Schema\Blueprint;
use Core\Database\Schema\SchemaDefinition;

class ProductCardFeatureTranslationsSchema extends SchemaDefinition
{
    protected string $table = 'product_card_feature_translations';

    public function define(Blueprint $table): void
    {
        $table->id();
        $table->foreignId('feature_id')->index();
        $table->string('locale', 10)->index();
        $table->string('label', 120);
        $table->string_255('feature_value');

        $table->foreign('feature_id')->references('id')->on('product_card_features')->onDelete('CASCADE');
        $table->unique('feature_id', 'locale');
    }
}
