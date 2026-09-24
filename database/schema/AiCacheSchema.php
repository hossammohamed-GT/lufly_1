<?php

declare(strict_types=1);

namespace Database\Schema;

use Core\Database\Schema\Blueprint;
use Core\Database\Schema\SchemaDefinition;

class AiCacheSchema extends SchemaDefinition
{
    protected string $table = 'ai_cache';

    public function define(Blueprint $table): void
    {
        $table->id();
        $table->string_50('scope');
        $table->string_100('fingerprint');
        $table->json('payload');
        $table->string_100('model')->nullable();
        $table->integer('hits')->default(0);
        $table->dateTime('expires_at')->nullable()->index();
        $table->timestamps();

        $table->unique('scope', 'fingerprint');
    }
}
