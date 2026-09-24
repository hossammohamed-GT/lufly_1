<?php

declare(strict_types=1);

namespace Core\Database\Schema;

use Core\Database\Connection;

class SchemaBuilder
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function create(string $table, callable $callback): void
    {
        $blueprint = new Blueprint($table);
        $callback($blueprint);

        foreach ($blueprint->toStatements($this->connection->driver()) as $sql) {
            $this->connection->exec($sql);
        }
    }

    public function createFromDefinition(SchemaDefinition $definition): void
    {
        foreach ($definition->blueprint()->toStatements($this->connection->driver()) as $sql) {
            $this->connection->exec($sql);
        }
    }

    public function dropIfExists(string $table): void
    {
        $this->connection->exec('DROP TABLE IF EXISTS ' . $table);
    }

    public function hasTable(string $table): bool
    {
        if ($this->connection->driver() === 'sqlite') {
            $row = $this->connection->selectOne(
                "SELECT name FROM sqlite_master WHERE type = 'table' AND name = ?",
                [$table],
            );
            return $row !== null;
        }

        $row = $this->connection->selectOne('SHOW TABLES LIKE ?', [$table]);
        return $row !== null;
    }

    public function listTables(): array
    {
        if ($this->connection->driver() === 'sqlite') {
            return array_column(
                $this->connection->select("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'"),
                'name',
            );
        }

        return array_map('reset', $this->connection->select('SHOW TABLES'));
    }

    public function dropAllTables(): void
    {
        if ($this->connection->driver() === 'mysql') {
            $this->connection->exec('SET FOREIGN_KEY_CHECKS = 0');
        }
        foreach ($this->listTables() as $table) {
            $this->dropIfExists($table);
        }
        if ($this->connection->driver() === 'mysql') {
            $this->connection->exec('SET FOREIGN_KEY_CHECKS = 1');
        }
    }
}
