<?php

declare(strict_types=1);

namespace Core\Database;

class QueryBuilder
{
    private string $table = '';

    private array $columns = ['*'];

    private array $wheres = [];

    private array $orders = [];

    private ?int $limit = null;

    private ?int $offset = null;

    private $hydrator = null;

    public function __construct(private readonly Connection $connection, ?string $table = null)
    {
        if ($table !== null) {
            $this->table = $table;
        }
    }

    public function table(string $table): static
    {
        $clone = clone $this;
        $clone->table = $table;
        return $clone;
    }

    public function connection(): Connection
    {
        return $this->connection;
    }

    public function select(string ...$columns): static
    {
        $this->columns = $columns !== [] ? $columns : ['*'];
        return $this;
    }

    public function where(string $column, mixed $operator, mixed $value = null): static
    {
        if ($value === null && !in_array($operator, [null], true) && !is_string($operator)) {

            $value = $operator;
            $operator = '=';
        } elseif ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = ['clause' => $this->column($column) . ' ' . $this->sanitizeOperator((string) $operator) . ' ?', 'bindings' => [$value]];
        return $this;
    }

    public function whereIn(string $column, array $values): static
    {
        if ($values === []) {
            $this->wheres[] = ['clause' => '1 = 0', 'bindings' => []];
            return $this;
        }

        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $this->wheres[] = ['clause' => $this->column($column) . ' IN (' . $placeholders . ')', 'bindings' => array_values($values)];
        return $this;
    }

    public function whereNull(string $column): static
    {
        $this->wheres[] = ['clause' => $this->column($column) . ' IS NULL', 'bindings' => []];
        return $this;
    }

    public function whereNotNull(string $column): static
    {
        $this->wheres[] = ['clause' => $this->column($column) . ' IS NOT NULL', 'bindings' => []];
        return $this;
    }

    public function whereLike(string $column, string $value): static
    {
        $this->wheres[] = ['clause' => $this->column($column) . ' LIKE ?', 'bindings' => [$value]];
        return $this;
    }

    public function orderBy(string $column, string $direction = 'asc'): static
    {
        $direction = strtolower($direction) === 'desc' ? 'DESC' : 'ASC';
        $this->orders[] = $this->column($column) . ' ' . $direction;
        return $this;
    }

    public function latest(string $column = 'created_at'): static
    {
        return $this->orderBy($column, 'desc');
    }

    public function limit(int $limit): static
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): static
    {
        $this->offset = $offset;
        return $this;
    }

    public function hydrateWith(callable $hydrator): static
    {
        $this->hydrator = $hydrator;
        return $this;
    }

    public function get(): array
    {
        $sql = $this->toSql();
        $rows = $this->connection->select($sql, $this->bindings());

        if ($this->hydrator === null) {
            return $rows;
        }

        return array_map($this->hydrator, $rows);
    }

    public function first(): mixed
    {
        $this->limit(1);
        $results = $this->get();

        return $results[0] ?? null;
    }

    public function count(): int
    {
        $sql = 'SELECT COUNT(*) AS aggregate FROM ' . $this->table . $this->whereSql();
        $row = $this->connection->selectOne($sql, $this->bindings());

        return (int) ($row['aggregate'] ?? 0);
    }

    public function value(string $column): mixed
    {
        $this->select($column);
        $row = $this->first();

        if ($row === null) {
            return null;
        }

        if (is_array($row)) {
            return $row[$column] ?? reset($row);
        }

        return $row->{$column} ?? null;
    }

    public function pluck(string $column): array
    {
        return array_map(
            static fn ($row) => is_array($row) ? ($row[$column] ?? null) : ($row->{$column} ?? null),
            $this->select($column)->get(),
        );
    }

    public function exists(): bool
    {
        return $this->count() > 0;
    }

    public function insert(array $values): int|string
    {
        return $this->connection->insert($this->table, $values);
    }

    public function update(array $values): int
    {
        $sets = implode(', ', array_map(fn ($c) => $this->column((string) $c) . ' = ?', array_keys($values)));
        $sql = 'UPDATE ' . $this->table . ' SET ' . $sets . $this->whereSql();

        return $this->connection->affect($sql, array_merge(array_values($values), $this->bindings()));
    }

    public function delete(): int
    {
        $sql = 'DELETE FROM ' . $this->table . $this->whereSql();

        return $this->connection->affect($sql, $this->bindings());
    }

    public function paginate(int $page = 1, int $perPage = 10): Paginator
    {
        $page = max(1, $page);
        $total = $this->count();

        $items = $this
            ->limit($perPage)
            ->offset(($page - 1) * $perPage)
            ->get();

        return new Paginator($items, $total, $page, $perPage);
    }

    public function toSql(): string
    {
        $columns = implode(', ', array_map(fn ($c) => $this->column((string) $c), $this->columns));
        $sql = 'SELECT ' . $columns . ' FROM ' . $this->table;
        $sql .= $this->whereSql();

        if ($this->orders !== []) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orders);
        }
        if ($this->limit !== null) {
            $sql .= ' LIMIT ' . $this->limit;
        }
        if ($this->offset !== null) {
            $sql .= ' OFFSET ' . $this->offset;
        }

        return $sql;
    }

    public function bindings(): array
    {
        $bindings = [];
        foreach ($this->wheres as $where) {
            $bindings = array_merge($bindings, $where['bindings']);
        }

        return $bindings;
    }

    private function whereSql(): string
    {
        if ($this->wheres === []) {
            return '';
        }

        $clauses = array_map(static fn ($w) => $w['clause'], $this->wheres);

        return ' WHERE ' . implode(' AND ', $clauses);
    }

    private function sanitizeOperator(string $operator): string
    {
        $allowed = ['=', '!=', '<>', '<', '>', '<=', '>=', 'LIKE', 'NOT LIKE'];
        $operator = strtoupper(trim($operator));

        return in_array($operator, $allowed, true) ? $operator : '=';
    }

    private function column(string $column): string
    {
        if ($column === '*' || str_contains($column, '(') || str_contains($column, ' ') || str_contains($column, '"') || str_contains($column, '`')) {
            return $column;
        }

        if (str_contains($column, '.')) {
            return implode('.', array_map(
                fn ($part) => $part === '*' ? '*' : $this->connection->quote($part),
                explode('.', $column),
            ));
        }

        return $this->connection->quote($column);
    }
}
