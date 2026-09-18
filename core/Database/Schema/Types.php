<?php

declare(strict_types=1);

namespace Core\Database\Schema;

/**
 * Reusable platform data types (single source of truth for column shapes).
 *
 * id | uuid | string_50 | string_100 | string_255 | email | phone | slug |
 * json | long_text | status | timestamps | soft_delete | seo
 */
final class Types
{
    public static function id(Blueprint $table, string $column = 'id'): ColumnDefinition
    {
        return $table->bigIncrements($column);
    }

    public static function uuid(Blueprint $table, string $column): ColumnDefinition
    {
        return $table->char($column, 36);
    }

    public static function string_50(Blueprint $table, string $column): ColumnDefinition
    {
        return $table->string($column, 50);
    }

    public static function string_100(Blueprint $table, string $column): ColumnDefinition
    {
        return $table->string($column, 100);
    }

    public static function string_150(Blueprint $table, string $column): ColumnDefinition
    {
        return $table->string($column, 150);
    }

    public static function string_255(Blueprint $table, string $column): ColumnDefinition
    {
        return $table->string($column, 255);
    }

    public static function email(Blueprint $table, string $column): ColumnDefinition
    {
        return $table->string($column, 255);
    }

    public static function phone(Blueprint $table, string $column): ColumnDefinition
    {
        return $table->string($column, 50);
    }

    public static function slug(Blueprint $table, string $column): ColumnDefinition
    {
        return $table->string($column, 255)->index();
    }

    public static function json(Blueprint $table, string $column): ColumnDefinition
    {
        return $table->json($column);
    }

    public static function long_text(Blueprint $table, string $column): ColumnDefinition
    {
        return $table->longText($column);
    }

    public static function status(Blueprint $table, string $column = 'status'): ColumnDefinition
    {
        return $table->string($column, 50)->default('active')->index();
    }

    public static function timestamps(Blueprint $table): void
    {
        $table->timestamp('created_at')->nullable();
        $table->timestamp('updated_at')->nullable();
    }

    public static function soft_delete(Blueprint $table): void
    {
        $table->timestamp('deleted_at')->nullable()->index();
    }

    public static function seo(Blueprint $table, string $column = 'seo'): ColumnDefinition
    {
        return $table->json($column)->nullable();
    }
}
