<?php

declare(strict_types=1);

namespace Core\Database;

use Core\Exceptions\DatabaseException;

class DatabaseManager
{
    private array $connections = [];

    public function connection(?string $name = null): Connection
    {
        $name ??= (string) config('database.default', 'mysql');

        if (!isset($this->connections[$name])) {
            $config = config("database.connections.{$name}");
            if (!is_array($config)) {
                throw new DatabaseException("Database connection [{$name}] is not configured.");
            }
            $this->connections[$name] = new Connection($config);
        }

        return $this->connections[$name];
    }

    public function query(?string $connection = null): QueryBuilder
    {
        return new QueryBuilder($this->connection($connection));
    }
}
