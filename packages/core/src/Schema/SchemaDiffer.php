<?php

declare(strict_types=1);

namespace Velm\Schema;

use Velm\Database\SqlQuote;
use Velm\Database\Connection;
use Velm\Fields\Field;
use Velm\Fields\Many2manyField;
use Velm\Fields\One2manyField;
use Velm\Models\Model;
use Velm\Registry;

final class SchemaDiffer
{
    private readonly SchemaBuilder $builder;
    private readonly LaravelSchema $schema;

    public function __construct(
        private readonly Connection $connection,
        ?SchemaBuilder $builder = null,
    ) {
        $this->builder = $builder ?? new SchemaBuilder($connection);
        $this->schema = LaravelSchema::for($connection);
    }

    /**
     * @param  list<class-string<Model>>  $modelClasses
     */
    public function compute(Registry $registry, array $modelClasses): SchemaDiff
    {
        $diff = new SchemaDiff;

        foreach ($modelClasses as $modelClass) {
            if ($modelClass::isExtension()) {
                continue;
            }

            $table = $modelClass::table();
            $expected = $this->expectedColumns($registry, $modelClass);
            $actual = $this->schema->columnListing($table);

            if ($actual === []) {
                $diff->newTables[] = [$table, $modelClass];

                continue;
            }

            foreach ($expected as $column => $field) {
                if (! in_array($column, $actual, true)) {
                    $diff->newColumns[] = [$table, $column, $field];

                    continue;
                }

                if ($column === 'id') {
                    continue;
                }

                $wantsRequired = $field->required;
                $nullable = $this->columnIsNullable($table, $column);

                if ($wantsRequired && $nullable) {
                    $diff->alterations[] = new SchemaAlteration(
                        $table,
                        $column,
                        'set_not_null',
                        'SET NOT NULL when no NULL rows remain',
                        $field,
                    );
                } elseif (! $wantsRequired && ! $nullable) {
                    $diff->alterations[] = new SchemaAlteration(
                        $table,
                        $column,
                        'drop_not_null',
                        'DROP NOT NULL',
                        $field,
                    );
                }
            }

            foreach ($actual as $column) {
                if ($column === 'id' || isset($expected[$column])) {
                    continue;
                }

                if (in_array($column, $modelClass::schemaExternalColumns(), true)) {
                    continue;
                }

                $diff->orphanColumns[] = [$table, $column];
            }
        }

        return $diff;
    }

    /**
     * @param  list<class-string<Model>>  $modelClasses
     */
    public function apply(Registry $registry, array $modelClasses, ?SchemaDiff $diff = null): SchemaApplyResult
    {
        $diff ??= $this->compute($registry, $modelClasses);
        $result = new SchemaApplyResult($diff);

        Registry::with($registry, function () use ($registry, $modelClasses, $diff, $result): void {
            foreach ($diff->newTables as [, $modelClass]) {
                $this->builder->ensureTable($modelClass);
            }

            foreach ($diff->newColumns as [$table, $column, $field]) {
                if (in_array($column, $this->schema->columnListing($table), true)) {
                    continue;
                }

                $this->schema->addFieldColumn($table, $field);
            }

            foreach ($diff->alterations as $alteration) {
                if ($alteration->field === null || ! $this->schema->supportsAlterColumnNullability()) {
                    continue;
                }

                if ($alteration->kind === 'drop_not_null') {
                    $this->schema->setColumnNullable($alteration->table, $alteration->field, true);
                }

                if ($alteration->kind === 'set_not_null') {
                    if ($this->countNullRows($alteration->table, $alteration->column) > 0) {
                        $result->skippedNotNull++;
                        $result->skippedNotNullColumns[] = $alteration->table.'.'.$alteration->column;

                        continue;
                    }

                    $this->schema->setColumnNullable($alteration->table, $alteration->field, false);
                    $result->setNotNull++;
                }
            }

            $this->builder->ensureRelationTables($registry);

            foreach ($modelClasses as $modelClass) {
                if ($modelClass::isExtension()) {
                    $inherit = $modelClass::inherit();

                    if ($inherit !== null && $registry->has($inherit)) {
                        $this->builder->applyColumnDiff($registry->baseModelClass($inherit));
                    }

                    continue;
                }

                $this->builder->ensureTable($modelClass);
            }
        });

        return $result;
    }

    public function countNullRows(string $table, string $column): int
    {
        $row = $this->connection->fetchOne(
            'SELECT COUNT(*) as c FROM '.SqlQuote::identifier($this->connection, $table)
            .' WHERE '.SqlQuote::identifier($this->connection, $column).' IS NULL',
        );

        return (int) ($row['c'] ?? $row['C'] ?? 0);
    }

    public function supportsAlterColumnNullability(): bool
    {
        return $this->schema->supportsAlterColumnNullability();
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @return array<string, Field>
     */
    private function expectedColumns(Registry $registry, string $modelClass): array
    {
        $name = $modelClass::name();
        $fields = $registry->has($name)
            ? $registry->fieldSet($name)
            : $modelClass::fields();

        $columns = [];

        foreach ($fields as $field) {
            if ($field->name === 'display_name' || $field instanceof Many2manyField || $field instanceof One2manyField || ! $field->persistsInDatabase()) {
                continue;
            }

            $columns[$field->column] = $field;
        }

        return $columns;
    }

    private function columnIsNullable(string $table, string $column): bool
    {
        if ($this->schema->driver() === 'sqlite') {
            foreach ($this->pragmaTableInfo($table) as $row) {
                if ((string) ($row['name'] ?? '') === $column) {
                    return (int) ($row['notnull'] ?? 1) === 0;
                }
            }
        }

        if (! $this->schema->supportsAlterColumnNullability()) {
            return true;
        }

        $schemaName = $this->schema->driver() === 'pgsql'
            ? 'public'
            : $this->connection->illuminateConnection()->getDatabaseName();

        $rows = $this->connection->fetchAll(
            'SELECT is_nullable FROM information_schema.columns '
            .'WHERE table_schema = ? AND table_name = ? AND column_name = ?',
            [$schemaName, $table, $column],
        );

        if ($rows === []) {
            return true;
        }

        $nullable = $rows[0]['is_nullable'] ?? $rows[0]['IS_NULLABLE'] ?? 'YES';

        return strtoupper((string) $nullable) === 'YES';
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function pragmaTableInfo(string $table): array
    {
        try {
            return $this->connection->fetchAll('PRAGMA table_info("'.$table.'")');
        } catch (\Throwable) {
            return [];
        }
    }
}
