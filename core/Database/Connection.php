<?php

declare(strict_types=1);

namespace Core\Database;

use Core\Exceptions\DatabaseException;
use Core\Logging\Log;
use PDO;
use PDOException;
use PDOStatement;
use Throwable;

class Connection
{
    private ?PDO $pdo = null;
    private int $queryCount = 0;

    /** @param array<string, mixed> $config */
    public function __construct(private readonly array $config)
    {
    }

    public function driver(): string
    {
        return (string) ($this->config['driver'] ?? 'mysql');
    }

    public function pdo(): PDO
    {
        if ($this->pdo !== null) {
            return $this->pdo;
        }

        try {
            $this->pdo = new PDO($this->dsn(), $this->user(), $this->password(), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            throw new DatabaseException('Database connection failed: ' . $e->getMessage());
        }

        return $this->pdo;
    }

    private function dsn(): string
    {
        $driver = $this->driver();
        if ($driver === 'sqlite') {
            $path = (string) ($this->config['database'] ?? ':memory:');
            if ($path !== ':memory:' && !str_starts_with($path, '/')) {
                $base = $GLOBALS['__app'] ?? null;
                $path = $base !== null ? $base->basePath($path) : $path;
            }
            return 'sqlite:' . $path;
        }

        return sprintf(
            '%s:host=%s;port=%s;dbname=%s;charset=%s',
            $driver,
            (string) ($this->config['host'] ?? '127.0.0.1'),
            (string) ($this->config['port'] ?? '3306'),
            (string) ($this->config['database'] ?? ''),
            (string) ($this->config['charset'] ?? 'utf8mb4'),
        );
    }

    private function user(): ?string
    {
        $user = $this->config['username'] ?? null;
        return $user === null ? null : (string) $user;
    }

    private function password(): ?string
    {
        $password = $this->config['password'] ?? null;
        return $password === null ? null : (string) $password;
    }

    public function getQueryCount(): int
    {
        return $this->queryCount;
    }

    public function resetQueryCount(): void
    {
        $this->queryCount = 0;
    }

    /** @param array<int|string, mixed> $bindings */
    public function query(string $sql, array $bindings = []): PDOStatement
    {
        $this->queryCount++;
        $this->log($sql, $bindings);

        try {
            $statement = $this->pdo()->prepare($sql);
            $statement->execute($bindings);
            return $statement;
        } catch (PDOException $e) {
            throw new DatabaseException('Query failed: ' . $e->getMessage() . ' [SQL: ' . $sql . ']');
        }
    }

    /** @param array<int|string, mixed> $bindings */
    public function select(string $sql, array $bindings = []): array
    {
        return $this->query($sql, $bindings)->fetchAll();
    }

    /** @param array<int|string, mixed> $bindings */
    public function selectOne(string $sql, array $bindings = []): ?array
    {
        $row = $this->query($sql, $bindings)->fetch();
        return $row === false ? null : $row;
    }

    /** @param array<int|string, mixed> $bindings */
    public function affect(string $sql, array $bindings = []): int
    {
        return $this->query($sql, $bindings)->rowCount();
    }

    public function exec(string $sql): void
    {
        $this->queryCount++;
        $this->log($sql, []);

        try {
            $this->pdo()->exec($sql);
        } catch (PDOException $e) {
            throw new DatabaseException('Statement failed: ' . $e->getMessage() . ' [SQL: ' . $sql . ']');
        }
    }

    /** @param array<string, mixed> $values */
    public function insert(string $table, array $values): int|string
    {
        $columns = array_keys($values);
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $table,
            implode(', ', array_map(fn ($c) => $this->quote((string) $c), $columns)),
            implode(', ', array_fill(0, count($columns), '?')),
        );

        $this->query($sql, array_values($values));

        return $this->pdo()->lastInsertId();
    }

    public function lastInsertId(): int|string
    {
        return $this->pdo()->lastInsertId();
    }

    /** @template T @param callable(): T $callback @return T */
    public function transaction(callable $callback): mixed
    {
        $this->pdo()->beginTransaction();

        try {
            $result = $callback();
            $this->pdo()->commit();
            return $result;
        } catch (Throwable $e) {
            if ($this->pdo()->inTransaction()) {
                $this->pdo()->rollBack();
            }
            throw $e;
        }
    }

    public function quote(string $identifier): string
    {
        return $this->driver() === 'mysql' ? '`' . str_replace('`', '', $identifier) . '`' : '"' . $identifier . '"';
    }

    public function table(string $table): QueryBuilder
    {
        return new QueryBuilder($this, $table);
    }

    /** @param array<int|string, mixed> $bindings */
    private function log(string $sql, array $bindings): void
    {
        if (config('database.log', true)) {
            Log::channel('database')->debug($sql, ['bindings' => array_map(
                static fn ($v) => is_scalar($v) || $v === null ? $v : get_debug_type($v),
                $bindings,
            )]);
        }
    }
}
