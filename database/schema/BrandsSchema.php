<?php

declare(strict_types=1);

namespace Database\Schema;

use Core\Database\Schema\Blueprint;
use Core\Database\Schema\SchemaDefinition;

class BrandsSchema extends SchemaDefinition
{
    protected string $table = 'brands';

    public function define(Blueprint $table): void
    {
        $table->id();
        $table->string_100('name');
        $table->slug()->unique();
        $table->string_255('logo')->nullable();
        $table->string_100('country')->nullable();
        $table->string_255('website')->nullable();
        $table->status();
        $table->timestamps();
        $table->soft_delete();
    }
}
