<?php

declare(strict_types=1);

namespace Core\Database\Schema;

class Blueprint
{
    private array $columns = [];

    private array $indexes = [];

    private array $foreignKeys = [];

    public function __construct(public readonly string $table)
    {
    }

    public function id(string $column = 'id'): ColumnDefinition
    {
        return Types::id($this, $column);
    }

    public function uuid(string $column): ColumnDefinition
    {
        return Types::uuid($this, $column);
    }

    public function string_50(string $column): ColumnDefinition
    {
        return Types::string_50($this, $column);
    }

    public function string_100(string $column): ColumnDefinition
    {
        return Types::string_100($this, $column);
    }

    public function string_150(string $column): ColumnDefinition
    {
        return Types::string_150($this, $column);
    }

    public function string_255(string $column): ColumnDefinition
    {
        return Types::string_255($this, $column);
    }

    public function email(string $column): ColumnDefinition
    {
        return Types::email($this, $column);
    }

    public function phone(string $column): ColumnDefinition
    {
        return Types::phone($this, $column);
    }

    public function slug(string $column = 'slug'): ColumnDefinition
    {
        return Types::slug($this, $column);
    }

    public function long_text(string $column): ColumnDefinition
    {
        return Types::long_text($this, $column);
    }

    public function status(string $column = 'status'): ColumnDefinition
    {
        return Types::status($this, $column);
    }

    public function timestamps(): void
    {
        Types::timestamps($this);
    }

    public function soft_delete(): void
    {
        Types::soft_delete($this);
    }

    public function seo(string $column = 'seo'): ColumnDefinition
    {
        return Types::seo($this, $column);
    }

    public function bigIncrements(string $column): ColumnDefinition
    {
        return $this->addColumn(new ColumnDefinition($column, 'BIGINT'))
            ->unsigned()
            ->autoIncrement()
            ->primary();
    }

    public function char(string $column, int $length): ColumnDefinition
    {
        return $this->addColumn(new ColumnDefinition($column, "CHAR({$length})"));
    }

    public function string(string $column, int $length = 255): ColumnDefinition
    {
        return $this->addColumn(new ColumnDefinition($column, "VARCHAR({$length})"));
    }

    public function text(string $column): ColumnDefinition
    {
        return $this->addColumn(new ColumnDefinition($column, 'TEXT'));
    }

    public function longText(string $column): ColumnDefinition
    {
        return $this->addColumn(new ColumnDefinition($column, 'LONGTEXT'));
    }

    public function json(string $column): ColumnDefinition
    {
        return $this->addColumn(new ColumnDefinition($column, 'JSON'));
    }

    public function integer(string $column): ColumnDefinition
    {
        return $this->addColumn(new ColumnDefinition($column, 'INTEGER'));
    }

    public function bigInteger(string $column): ColumnDefinition
    {
        return $this->addColumn(new ColumnDefinition($column, 'BIGINT'));
    }

    public function boolean(string $column): ColumnDefinition
    {
        return $this->addColumn(new ColumnDefinition($column, 'TINYINT(1)'));
    }

    public function decimal(string $column, int $precision = 10, int $scale = 2): ColumnDefinition
    {
        return $this->addColumn(new ColumnDefinition($column, "DECIMAL({$precision},{$scale})"));
    }

    public function date(string $column): ColumnDefinition
    {
        return $this->addColumn(new ColumnDefinition($column, 'DATE'));
    }

    public function dateTime(string $column): ColumnDefinition
    {
        return $this->addColumn(new ColumnDefinition($column, 'DATETIME'));
    }

    public function timestamp(string $column): ColumnDefinition
    {
        return $this->addColumn(new ColumnDefinition($column, 'DATETIME'));
    }

    public function foreignId(string $column): ColumnDefinition
    {
        return $this->addColumn(new ColumnDefinition($column, 'BIGINT'))->unsigned();
    }

    public function foreign(string $column): ForeignKeyDefinition
    {
        $foreign = new ForeignKeyDefinition($column);
        $this->foreignKeys[] = $foreign;

        return $foreign;
    }

    public function unique(string ...$columns): void
    {
        $this->indexes[] = ['columns' => $columns, 'unique' => true];
    }

    public function index(string ...$columns): void
    {
        $this->indexes[] = ['columns' => $columns, 'unique' => false];
    }

    private function addColumn(ColumnDefinition $column): ColumnDefinition
    {
        $this->columns[] = $column;
        return $column;
    }

    public function columns(): array
    {
        return $this->columns;
    }

    public function foreignKeys(): array
    {
        return $this->foreignKeys;
    }

    public function toStatements(string $driver): array
    {
        $quote = static fn (string $identifier): string => $driver === 'mysql'
            ? '`' . str_replace('`', '', $identifier) . '`'
            : $identifier;

        $lines = [];
        foreach ($this->columns as $column) {
            $lines[] = $column->toSql($driver);
            if ($column->isIndex && $driver === 'mysql') {
                $lines[] = 'KEY ' . $this->indexName([$column->name], false) . ' (' . $quote($column->name) . ')';
            }
        }
        foreach ($this->indexes as $index) {
            if ($index['unique'] && $driver === 'mysql') {
                $columns = implode(', ', array_map($quote, $index['columns']));
                $lines[] = 'UNIQUE KEY ' . $this->indexName($index['columns'], true) . ' (' . $columns . ')';
            } elseif (!$index['unique'] && $driver === 'mysql') {
                $columns = implode(', ', array_map($quote, $index['columns']));
                $lines[] = 'KEY ' . $this->indexName($index['columns'], false) . ' (' . $columns . ')';
            }
        }
        foreach ($this->foreignKeys as $foreign) {
            $lines[] = $foreign->toSql($driver);
        }

        $table = $driver === 'mysql' ? $quote($this->table) : $this->table;
        $statements = [sprintf("CREATE TABLE %s (\n    %s\n)", $table, implode(",\n    ", $lines))];

        if ($driver === 'sqlite') {
            foreach ($this->indexes as $index) {
                $statements[] = sprintf(
                    'CREATE %sINDEX %s ON %s (%s)',
                    $index['unique'] ? 'UNIQUE ' : '',
                    $this->indexName($index['columns'], $index['unique']),
                    $this->table,
                    implode(', ', $index['columns']),
                );
            }
        }

        if ($driver === 'sqlite') {
            foreach ($this->columns as $column) {
                if ($column->isIndex) {
                    $statements[] = sprintf(
                        'CREATE INDEX %s_%s_index ON %s (%s)',
                        $this->table,
                        $column->name,
                        $this->table,
                        $column->name,
                    );
                }
            }
        }

        return $statements;
    }

    private function indexName(array $columns, bool $unique): string
    {
        $name = $this->table . '_' . implode('_', $columns) . ($unique ? '_unique' : '_index');


        if (strlen($name) > 64) {
            $name = rtrim(substr($name, 0, 53), '_') . '_' . substr(md5($name), 0, 10);
        }

        return $name;
    }
}
