<?php

declare(strict_types=1);

namespace Database\Schema;

use Core\Database\Schema\Blueprint;
use Core\Database\Schema\SchemaDefinition;

class FavoritesSchema extends SchemaDefinition
{
    protected string $table = 'favorites';

    public function define(Blueprint $table): void
    {
        $table->id();
        $table->string_100('token')->unique();
        $table->email('email')->nullable();
        $table->string_50('locale')->default('en');
        $table->foreignId('user_id')->nullable()->index();
        $table->string_100('ip_hash')->nullable();
        $table->string_255('user_agent')->nullable();
        $table->integer('emails_sent')->default(0);
        $table->integer('emails_today')->default(0);
        $table->dateTime('last_emailed_at')->nullable();
        $table->dateTime('claimed_at')->nullable();

        $table->boolean('notify')->nullable();
        $table->timestamps();
        $table->soft_delete();

        $table->foreign('user_id')->references('id')->on('users')->onDelete('SET NULL');
    }
}
