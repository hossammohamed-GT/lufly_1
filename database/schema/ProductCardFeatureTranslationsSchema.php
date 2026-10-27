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
        $table->foreignId('feature_id');
        $table->string_50('locale')->index();
        $table->string_150('label')->nullable();
        $table->string_255('value');

        $table->foreign('feature_id')->references('id')->on('product_card_features')->onDelete('CASCADE');
        $table->unique('feature_id', 'locale');
    }
}
