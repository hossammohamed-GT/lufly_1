<?php

declare(strict_types=1);

namespace Database\Schema;

use Core\Database\Schema\Blueprint;
use Core\Database\Schema\SchemaDefinition;

class AttributeOptionsSchema extends SchemaDefinition
{
    protected string $table = 'attribute_options';

    public function define(Blueprint $table): void
    {
        $table->id();
        $table->foreignId('attribute_id')->index();
        $table->string_150('value');
        $table->string_50('color_hex')->nullable();
        $table->integer('sort_order')->default(0);
        $table->timestamps();

        $table->foreign('attribute_id')->references('id')->on('attributes')->onDelete('CASCADE');
        $table->unique('attribute_id', 'value');
    }
}
