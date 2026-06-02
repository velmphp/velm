<?php

declare(strict_types=1);

namespace Velm\Migrations;

use Velm\Environment;

final class Schema
{
    public function __construct(
        private readonly Environment $env,
    ) {}

    public static function make(Environment $env): self
    {
        return new self($env);
    }

    /**
     * @param  callable(Table): void  $blueprint
     */
    public function create(string $table, callable $blueprint): void
    {
        if ($this->tableExists($table)) {
            return;
        }

        $definition = new Table;
        $blueprint($definition);

        $parts = ['"id" INTEGER PRIMARY KEY AUTOINCREMENT'];

        foreach ($definition->columns() as $column) {
            $parts[] = $this->columnSql($column);
        }

        $this->env->connection->execute(
            'CREATE TABLE "'.$table.'" ('.implode(', ', $parts).')',
        );
    }

    /**
     * @param  callable(Table): void  $blueprint
     */
    public function table(string $table, callable $blueprint): void
    {
        if (! $this->tableExists($table)) {
            throw new \RuntimeException("Table {$table} does not exist.");
        }

        $definition = new Table;
        $blueprint($definition);

        foreach ($definition->columns() as $column) {
            if ($this->columnExists($table, $column->name)) {
                continue;
            }

            $this->env->connection->execute(
                'ALTER TABLE "'.$table.'" ADD COLUMN '.$this->columnSql($column),
            );
        }
    }

    private function columnSql(ColumnDefinition $column): string
    {
        $sql = '"'.$column->name.'" '.$column->sqlType;
        $sql .= $column->nullable ? '' : ' NOT NULL';

        if ($column->default !== null) {
            if (is_bool($column->default)) {
                $sql .= ' DEFAULT '.($column->default ? '1' : '0');
            } elseif (is_int($column->default)) {
                $sql .= ' DEFAULT '.$column->default;
            } elseif (is_string($column->default)) {
                $sql .= " DEFAULT '".$column->default."'";
            }
        }

        return $sql;
    }

    private function tableExists(string $table): bool
    {
        $rows = $this->env->connection->fetchAll('PRAGMA table_info("'.$table.'")');

        if ($rows !== []) {
            return true;
        }

        try {
            $this->env->connection->fetchAll('SELECT DATABASE() as db');
            $database = $this->env->connection->fetchOne('SELECT DATABASE() as db')['db'] ?? null;

            if (! is_string($database)) {
                return false;
            }

            $found = $this->env->connection->fetchAll(
                'SELECT 1 FROM information_schema.tables WHERE table_schema = ? AND table_name = ?',
                [$database, $table],
            );

            return $found !== [];
        } catch (\Throwable) {
            return false;
        }
    }

    private function columnExists(string $table, string $column): bool
    {
        foreach ($this->env->connection->fetchAll('PRAGMA table_info("'.$table.'")') as $row) {
            if ((string) ($row['name'] ?? '') === $column) {
                return true;
            }
        }

        return false;
    }
}
