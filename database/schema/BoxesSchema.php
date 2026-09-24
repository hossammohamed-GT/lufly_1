<?php

declare(strict_types=1);

namespace Database\Schema;

use Core\Database\Schema\Blueprint;
use Core\Database\Schema\SchemaDefinition;

class BoxesSchema extends SchemaDefinition
{
    protected string $table = 'boxes';

    public function define(Blueprint $table): void
    {
        $table->id();
        $table->string_100('token')->unique();
        $table->email('email')->nullable();
        $table->string_50('locale')->default('en');
        $table->text('note')->nullable();
        $table->string_100('ip_hash')->nullable();
        $table->string_255('user_agent')->nullable();
        $table->integer('sends')->default(0);
        $table->integer('sends_today')->default(0);
        $table->dateTime('last_sent_at')->nullable();
        $table->dateTime('notified_at')->nullable();
        $table->dateTime('claimed_at')->nullable();
        $table->timestamps();
        $table->soft_delete();
    }
}
