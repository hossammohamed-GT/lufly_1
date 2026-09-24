<?php

declare(strict_types=1);

namespace Database\Schema;

use Core\Database\Schema\Blueprint;
use Core\Database\Schema\SchemaDefinition;

class CollectionTranslationsSchema extends SchemaDefinition
{
    protected string $table = 'collection_translations';

    public function define(Blueprint $table): void
    {
        $table->id();
        $table->foreignId('collection_id');
        $table->string_50('locale')->index();
        $table->string_255('name');
        $table->long_text('description')->nullable();

        $table->foreign('collection_id')->references('id')->on('collections')->onDelete('CASCADE');
        $table->unique('collection_id', 'locale');
    }
}
