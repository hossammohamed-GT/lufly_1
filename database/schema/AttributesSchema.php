<?php

declare(strict_types=1);

namespace Database\Schema;

use Core\Database\Schema\Blueprint;
use Core\Database\Schema\SchemaDefinition;

class AttributesSchema extends SchemaDefinition
{
    protected string $table = 'attributes';

    public function define(Blueprint $table): void
    {
        $table->id();
        $table->string_100('code')->unique();
        $table->string_150('name');
        $table->string_50('type')->default('select'); // select|text|number|boolean|color
        $table->boolean('is_filterable')->default(1);
        $table->boolean('is_sortable')->default(0);
        $table->timestamps();
        $table->soft_delete();
    }
}
