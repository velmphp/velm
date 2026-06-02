<?php

declare(strict_types=1);

namespace Velm\Migrations;

final class Table
{
    /** @var list<ColumnDefinition> */
    private array $columns = [];

    public function id(): self
    {
        return $this;
    }

    public function string(string $name, ?int $length = null, bool $nullable = true): self
    {
        $sql = $length !== null ? "VARCHAR({$length})" : 'TEXT';

        $this->columns[] = new ColumnDefinition($name, $sql, $nullable);

        return $this;
    }

    public function text(string $name, bool $nullable = true): self
    {
        $this->columns[] = new ColumnDefinition($name, 'TEXT', $nullable);

        return $this;
    }

    public function integer(string $name, bool $nullable = true): self
    {
        $this->columns[] = new ColumnDefinition($name, 'INTEGER', $nullable);

        return $this;
    }

    public function boolean(string $name, bool $nullable = true, mixed $default = null): self
    {
        $this->columns[] = new ColumnDefinition($name, 'BOOLEAN', $nullable, $default);

        return $this;
    }

    public function dropColumn(string $name): self
    {
        throw new \RuntimeException(
            "dropColumn({$name}) is not auto-applied — use SYNC_HOOK or raw SQL in a migration script.",
        );
    }

    /**
     * @return list<ColumnDefinition>
     */
    public function columns(): array
    {
        return $this->columns;
    }
}
