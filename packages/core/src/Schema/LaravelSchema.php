<?php

declare(strict_types=1);

namespace Velm\Schema;

use Illuminate\Database\Connection as IlluminateConnection;
use Illuminate\Database\Schema\Blueprint;
use Velm\Database\Connection;
use Velm\Fields\Field;
use Velm\Migrations\ColumnDefinition as MigrationColumnDefinition;
use Velm\Migrations\MigrationColumnBlueprint;

final class LaravelSchema
{
    public function __construct(
        private readonly IlluminateConnection $connection,
    ) {}

    public static function for(Connection $connection): self
    {
        return new self($connection->illuminateConnection());
    }

    public function driver(): string
    {
        return $this->connection->getDriverName();
    }

    public function hasTable(string $table): bool
    {
        return $this->builder()->hasTable($table);
    }

    /**
     * @return list<string>
     */
    public function columnListing(string $table): array
    {
        if (! $this->hasTable($table)) {
            return [];
        }

        return $this->builder()->getColumnListing($table);
    }

    /**
     * @param  iterable<Field>  $fields
     */
    public function createModelTable(string $table, iterable $fields): void
    {
        if ($this->hasTable($table)) {
            return;
        }

        $this->builder()->create($table, function (Blueprint $blueprint) use ($fields): void {
            $blueprint->increments('id');

            foreach ($fields as $field) {
                if ($field->name === 'id' || ! $field->persistsInDatabase()) {
                    continue;
                }

                FieldBlueprint::addColumn($blueprint, $field);
            }
        });
    }

    public function addFieldColumn(string $table, Field $field): void
    {
        if (in_array($field->column, $this->columnListing($table), true)) {
            return;
        }

        $this->builder()->table($table, function (Blueprint $blueprint) use ($field): void {
            FieldBlueprint::addColumn($blueprint, $field);
        });
    }

    public function createMany2manyTable(string $table, string $col1, string $col2): void
    {
        if ($this->hasTable($table)) {
            return;
        }

        $this->builder()->create($table, function (Blueprint $blueprint) use ($col1, $col2): void {
            $blueprint->unsignedInteger($col1);
            $blueprint->unsignedInteger($col2);
            $blueprint->primary([$col1, $col2]);
        });
    }

    public function createMigrationTable(string $table, iterable $columns): void
    {
        if ($this->hasTable($table)) {
            return;
        }

        $this->builder()->create($table, function (Blueprint $blueprint) use ($columns): void {
            $blueprint->increments('id');

            foreach ($columns as $column) {
                if ($column instanceof MigrationColumnDefinition) {
                    MigrationColumnBlueprint::addColumn($blueprint, $column);
                }
            }
        });
    }

    public function addMigrationColumn(string $table, MigrationColumnDefinition $column): void
    {
        if (in_array($column->name, $this->columnListing($table), true)) {
            return;
        }

        $this->builder()->table($table, function (Blueprint $blueprint) use ($column): void {
            MigrationColumnBlueprint::addColumn($blueprint, $column);
        });
    }

    public function setColumnNullable(string $table, Field $field, bool $nullable): void
    {
        $this->builder()->table($table, function (Blueprint $blueprint) use ($field, $nullable): void {
            $column = FieldBlueprint::defineColumn($blueprint, $field);

            if ($nullable) {
                $column->nullable();
            } else {
                $column->nullable(false);
            }

            $column->change();
        });
    }

    public function supportsAlterColumnNullability(): bool
    {
        return $this->driver() !== 'sqlite';
    }

    private function builder(): \Illuminate\Database\Schema\Builder
    {
        return $this->connection->getSchemaBuilder();
    }
}
