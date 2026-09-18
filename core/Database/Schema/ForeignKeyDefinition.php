<?php

declare(strict_types=1);

namespace Core\Database\Schema;

final class ForeignKeyDefinition
{
    private string $referencedColumn = 'id';

    private string $referencedTable = '';

    private string $onDelete = 'CASCADE';

    public function __construct(public readonly string $column)
    {
    }

    public function references(string $column): self
    {
        $this->referencedColumn = $column;
        return $this;
    }

    public function on(string $table): self
    {
        $this->referencedTable = $table;
        return $this;
    }

    public function onDelete(string $action): self
    {
        $this->onDelete = strtoupper($action);
        return $this;
    }

    public function referencedTable(): string
    {
        return $this->referencedTable;
    }

    public function referencedColumn(): string
    {
        return $this->referencedColumn;
    }

    public function toSql(string $driver = 'mysql'): string
    {
        $quote = static fn (string $identifier): string => $driver === 'mysql'
            ? '`' . str_replace('`', '', $identifier) . '`'
            : $identifier;

        return sprintf(
            'FOREIGN KEY (%s) REFERENCES %s (%s) ON DELETE %s',
            $quote($this->column),
            $quote($this->referencedTable),
            $quote($this->referencedColumn),
            $this->onDelete,
        );
    }
}
