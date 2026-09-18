<?php

declare(strict_types=1);

namespace Core\Database\Schema;

final class ColumnDefinition
{
    public bool $isNullable = false;

    public mixed $defaultValue = null;

    public bool $hasDefault = false;

    public bool $isUnsigned = false;

    public bool $isUnique = false;

    public bool $isIndex = false;

    public bool $isPrimary = false;

    public bool $isAutoIncrement = false;

    public function __construct(
        public readonly string $name,
        public readonly string $type,
    ) {
    }

    public function nullable(): self
    {
        $this->isNullable = true;
        return $this;
    }

    public function default(mixed $value): self
    {
        $this->defaultValue = $value;
        $this->hasDefault = true;
        return $this;
    }

    public function unsigned(): self
    {
        $this->isUnsigned = true;
        return $this;
    }

    public function unique(): self
    {
        $this->isUnique = true;
        return $this;
    }

    public function index(): self
    {
        $this->isIndex = true;
        return $this;
    }

    public function primary(): self
    {
        $this->isPrimary = true;
        return $this;
    }

    public function autoIncrement(): self
    {
        $this->isAutoIncrement = true;
        return $this;
    }

    public function toSql(string $driver): string
    {
        if ($driver === 'sqlite') {
            return $this->toSqlite();
        }

        $name = str_contains($this->name, '`') ? $this->name : "`{$this->name}`";
        $sql = "{$name} {$this->type}";
        if ($this->isUnsigned && str_contains(strtoupper($this->type), 'INT')) {
            $sql .= ' UNSIGNED';
        }
        $sql .= $this->isNullable ? ' NULL' : ' NOT NULL';
        if ($this->hasDefault) {
            $sql .= ' DEFAULT ' . $this->quoteDefault($driver);
        }
        if ($this->isAutoIncrement) {
            $sql .= ' AUTO_INCREMENT';
        }
        if ($this->isUnique) {
            $sql .= ' UNIQUE';
        }
        if ($this->isPrimary) {
            $sql .= ' PRIMARY KEY';
        }

        return $sql;
    }

    private function toSqlite(): string
    {
        $type = $this->type;
        if ($this->isAutoIncrement) {
            return "{$this->name} INTEGER PRIMARY KEY AUTOINCREMENT";
        }

        $sql = "{$this->name} {$type}";
        $sql .= $this->isNullable ? ' NULL' : ' NOT NULL';
        if ($this->hasDefault) {
            $sql .= ' DEFAULT ' . $this->quoteDefault('sqlite');
        }
        if ($this->isUnique) {
            $sql .= ' UNIQUE';
        }
        if ($this->isPrimary) {
            $sql .= ' PRIMARY KEY';
        }

        return $sql;
    }

    private function quoteDefault(string $driver): string
    {
        if ($this->defaultValue === null) {
            return 'NULL';
        }
        if (is_bool($this->defaultValue)) {
            return $this->defaultValue ? '1' : '0';
        }
        if (is_int($this->defaultValue) || is_float($this->defaultValue)) {
            return (string) $this->defaultValue;
        }
        if ($driver === 'mysql' && strtoupper((string) $this->defaultValue) === 'CURRENT_TIMESTAMP') {
            return 'CURRENT_TIMESTAMP';
        }

        return "'" . str_replace("'", "''", (string) $this->defaultValue) . "'";
    }
}
