<?php

declare(strict_types=1);

namespace Core\Database\Migration;

use Core\Database\Connection;
use Core\Database\Schema\SchemaBuilder;
use Core\Exceptions\DatabaseException;

class Migrator
{
    private const TABLE = 'migrations';

    public function __construct(
        private readonly Connection $db,
        private readonly SchemaBuilder $schema,
        private readonly string $path,
    ) {
    }

    public function ensureTable(): void
    {
        if ($this->schema->hasTable(self::TABLE)) {
            return;
        }

        $this->schema->create(self::TABLE, function ($table): void {
            $table->id();
            $table->string_255('migration');
            $table->integer('batch');
            $table->timestamp('applied_at')->nullable();
        });
    }

    public function applied(): array
    {
        $this->ensureTable();

        return array_column(
            $this->db->select('SELECT migration FROM ' . self::TABLE . ' ORDER BY id'),
            'migration',
        );
    }

    public function pending(): array
    {
        $applied = $this->applied();

        return array_values(array_filter(
            $this->files(),
            static fn (string $file): bool => !in_array($file, $applied, true),
        ));
    }

    public function migrate(): array
    {
        $pending = $this->pending();
        if ($pending === []) {
            return [];
        }

        $batch = $this->nextBatch();
        foreach ($pending as $file) {
            $migration = $this->resolve($file);
            $migration->up();
            $this->db->insert(self::TABLE, [
                'migration' => $file,
                'batch' => $batch,
                'applied_at' => date('Y-m-d H:i:s'),
            ]);
        }

        return $pending;
    }

    public function rollback(int $steps = 1): array
    {
        $this->ensureTable();
        $rolledBack = [];

        for ($i = 0; $i < $steps; $i++) {
            $rows = $this->db->select(
                'SELECT migration FROM ' . self::TABLE . ' WHERE batch = (SELECT MAX(batch) FROM ' . self::TABLE . ') ORDER BY id DESC',
            );
            if ($rows === []) {
                break;
            }

            foreach ($rows as $row) {
                $file = $row['migration'];
                $this->resolve($file)->down();
                $this->db->affect('DELETE FROM ' . self::TABLE . ' WHERE migration = ?', [$file]);
                $rolledBack[] = $file;
            }
        }

        return $rolledBack;
    }

    public function fresh(): array
    {
        $this->schema->dropAllTables();

        return $this->migrate();
    }

    private function files(): array
    {
        if (!is_dir($this->path)) {
            return [];
        }

        $files = array_values(array_filter(
            scandir($this->path) ?: [],
            static fn (string $f): bool => str_ends_with($f, '.php'),
        ));
        sort($files);

        return $files;
    }

    private function resolve(string $file): Migration
    {
        $full = $this->path . DIRECTORY_SEPARATOR . $file;
        if (!is_file($full)) {
            throw new DatabaseException("Migration file [{$file}] not found.");
        }

        $class = $this->className($file);
        if (!class_exists($class)) {
            require $full;
        }
        if (!class_exists($class)) {
            throw new DatabaseException("Migration class [{$class}] not found in [{$file}].");
        }

        return new $class($this->db, $this->schema);
    }

    private function className(string $file): string
    {
        $base = str_replace('.php', '', $file);
        $base = preg_replace('/^\d{4}_\d{2}_\d{2}_\d{6}_/', '', $base) ?? $base;

        return 'Database\\Migrations\\' . str_replace(' ', '', ucwords(str_replace('_', ' ', $base)));
    }

    private function nextBatch(): int
    {
        $row = $this->db->selectOne('SELECT MAX(batch) AS batch FROM ' . self::TABLE);

        return ((int) ($row['batch'] ?? 0)) + 1;
    }
}
