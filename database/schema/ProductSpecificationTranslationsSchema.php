<?php

declare(strict_types=1);

namespace Database\Schema;

use Core\Database\Schema\Blueprint;
use Core\Database\Schema\SchemaDefinition;

/**
 * Per-locale labels and values for product_specifications so technical
 * specification tables translate cleanly across en, tr, and cs.
 */
class ProductSpecificationTranslationsSchema extends SchemaDefinition
{
    protected string $table = 'product_specification_translations';

    public function define(Blueprint $table): void
    {
        $table->id();
        $table->foreignId('specification_id');
        $table->string_50('locale')->index();
        $table->string_150('spec_key');
        $table->string_255('spec_value');

        $table->foreign('specification_id')->references('id')->on('product_specifications')->onDelete('CASCADE');
        $table->unique('specification_id', 'locale');
    }
}
