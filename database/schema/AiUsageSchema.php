<?php

declare(strict_types=1);

namespace Database\Schema;

use Core\Database\Schema\Blueprint;
use Core\Database\Schema\SchemaDefinition;

class AiUsageSchema extends SchemaDefinition
{
    protected string $table = 'ai_usage';

    public function define(Blueprint $table): void
    {
        $table->id();
        $table->string_50('scope');
        $table->string_100('ip_hash')->index();
        $table->integer('key_slot')->default(0);
        $table->string_100('model')->nullable();
        $table->integer('prompt_tokens')->default(0);
        $table->integer('output_tokens')->default(0);
        $table->integer('duration_ms')->default(0);
        $table->boolean('ok')->default(true);
        $table->date('day')->index();
        $table->string_255('error')->nullable();
        $table->timestamps();
    }
}
