<?php

declare(strict_types=1);

namespace Database\Schema;

use Core\Database\Schema\Blueprint;
use Core\Database\Schema\SchemaDefinition;

/**
 * A visitor's saved-products list ("favorites" / wishlist).
 *
 * Storefront visitors are anonymous, so a list is identified by a random
 * `token`: it is stored in a long lived cookie so the visitor finds the list
 * again on the same browser, and it is printed in the e-mail so the same list
 * can be opened from any device (the `/{locale}/favorites/{token}` link).
 *
 * Nothing personal is required to save products; the e-mail address is only
 * filled in when the visitor asks for the list to be mailed.
 */
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

        /* "e-mail me whenever I save something new": null = the visitor was
           never asked, 1 = wants the update, 0 = asked and declined. */
        $table->boolean('notify')->nullable();
        $table->timestamps();
        $table->soft_delete();

        $table->foreign('user_id')->references('id')->on('users')->onDelete('SET NULL');
    }
}
