<?php

declare(strict_types=1);

namespace Core\Database;

abstract class Model
{
    protected static string $table = '';

    protected static string $primaryKey = 'id';

    protected static array $fillable = [];

    protected static array $casts = [];

    protected static bool $timestamps = true;

    protected static bool $softDelete = true;

    protected array $attributes = [];

    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    public static function table(): string
    {
        return static::$table;
    }

    public static function primaryKey(): string
    {
        return static::$primaryKey;
    }

    protected static function db(): DatabaseManager
    {
        return app(DatabaseManager::class);
    }

    public static function query(): QueryBuilder
    {
        $builder = static::db()->connection()->table(static::table())
            ->hydrateWith(fn (array $row): object => static::fromRow($row));

        if (static::$softDelete) {
            $builder->whereNull('deleted_at');
        }

        return $builder;
    }

    public static function withTrashed(): QueryBuilder
    {
        return static::db()->connection()->table(static::table())
            ->hydrateWith(fn (array $row): object => static::fromRow($row));
    }

    public static function fromRow(array $row): static
    {
        $model = new static();
        $model->attributes = $row;

        return $model;
    }

    public static function find(int|string $id): ?static
    {
        $model = static::query()->where(static::$primaryKey, $id)->first();

        return $model;
    }

    public static function all(array $orderBy = ['id' => 'desc'], int $limit = 0): array
    {
        $builder = static::query();
        foreach ($orderBy as $column => $direction) {
            $builder->orderBy((string) $column, (string) $direction);
        }
        if ($limit > 0) {
            $builder->limit($limit);
        }

        $items = $builder->get();

        return $items;
    }

    public static function create(array $data): static
    {
        $model = new static($data);
        $model->save();

        return $model;
    }

    public function fill(array $data): static
    {
        $fillable = static::$fillable;
        foreach ($data as $key => $value) {
            if ($fillable === [] || in_array($key, $fillable, true)) {
                $this->setAttribute((string) $key, $value);
            }
        }

        return $this;
    }

    public function getAttribute(string $key): mixed
    {
        $value = $this->attributes[$key] ?? null;

        return $this->castForRead($key, $value);
    }

    public function setAttribute(string $key, mixed $value): void
    {
        $this->attributes[$key] = $this->castForWrite($key, $value);
    }

    public function attributes(): array
    {
        $out = [];
        foreach (array_keys($this->attributes) as $key) {
            $out[$key] = $this->getAttribute($key);
        }

        return $out;
    }

    public function toArray(): array
    {
        return $this->attributes();
    }

    public function getKey(): mixed
    {
        return $this->attributes[static::$primaryKey] ?? null;
    }

    public function save(): bool
    {
        $now = date('Y-m-d H:i:s');

        if (static::$timestamps) {
            $this->attributes['updated_at'] = $now;
            if ($this->getKey() === null && !isset($this->attributes['created_at'])) {
                $this->attributes['created_at'] = $now;
            }
        }

        $values = $this->attributesForWrite();

        if ($this->getKey() === null) {
            unset($values[static::$primaryKey]);
            $id = static::db()->connection()->insert(static::table(), $values);
            if ($id !== '0' && $id !== '' && $id !== null) {
                $this->attributes[static::$primaryKey] = is_numeric($id) ? (int) $id : $id;
            }
            return true;
        }

        unset($values[static::$primaryKey]);
        static::db()->connection()->table(static::table())
            ->where(static::$primaryKey, $this->getKey())
            ->update($values);

        return true;
    }

    public function update(array $data): bool
    {
        $this->fill($data);

        return $this->save();
    }

    public function delete(): bool
    {
        if ($this->getKey() === null) {
            return false;
        }

        if (static::$softDelete) {
            return static::db()->connection()->table(static::table())
                ->where(static::$primaryKey, $this->getKey())
                ->update(['deleted_at' => date('Y-m-d H:i:s')]) > 0;
        }

        return static::db()->connection()->table(static::table())
            ->where(static::$primaryKey, $this->getKey())
            ->delete() > 0;
    }

    public function restore(): bool
    {
        if (!static::$softDelete || $this->getKey() === null) {
            return false;
        }

        return static::db()->connection()->table(static::table())
            ->where(static::$primaryKey, $this->getKey())
            ->update(['deleted_at' => null]) > 0;
    }

    protected function attributesForWrite(): array
    {
        $values = [];
        foreach ($this->attributes as $key => $value) {
            $values[$key] = $this->castForWrite($key, $value);
        }

        return $values;
    }

    protected function castForRead(string $key, mixed $value): mixed
    {
        $cast = static::$casts[$key] ?? null;

        return match ($cast) {
            'int', 'integer' => $value === null ? null : (int) $value,
            'bool', 'boolean' => $value === null ? null : (bool) $value,
            'json' => is_string($value) ? (json_decode($value, true) ?? []) : ($value ?? []),
            default => $value,
        };
    }

    protected function castForWrite(string $key, mixed $value): mixed
    {
        $cast = static::$casts[$key] ?? null;

        if ($cast === 'json' && is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        if ($cast === 'bool' || $cast === 'boolean') {
            return $value === null ? null : ($value ? 1 : 0);
        }

        return $value;
    }

    public function __get(string $key): mixed
    {
        return $this->getAttribute($key);
    }

    public function __set(string $key, mixed $value): void
    {
        $this->setAttribute($key, $value);
    }

    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }
}
