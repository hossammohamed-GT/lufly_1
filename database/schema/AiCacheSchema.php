<?php

declare(strict_types=1);

namespace Database\Schema;

use Core\Database\Schema\Blueprint;
use Core\Database\Schema\SchemaDefinition;

/**
 * Stored AI answers ("never ask the same question twice").
 *
 * scope + fingerprint is the lookup key: the scope names the feature
 * (planner, product-finder, label), the fingerprint hashes the exact question
 * and its inputs (including the bytes of an uploaded picture).
 */
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
