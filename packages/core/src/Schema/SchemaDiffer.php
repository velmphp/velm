<?php

declare(strict_types=1);

namespace Velm\Schema;

use Velm\Database\Connection;
use Velm\Fields\Field;
use Velm\Fields\Many2manyField;
use Velm\Models\Model;
use Velm\Registry;

final class SchemaDiffer
{
    private readonly SchemaBuilder $builder;

    public function __construct(
        private readonly Connection $connection,
        ?SchemaBuilder $builder = null,
    ) {
        $this->builder = $builder ?? new SchemaBuilder($connection);
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
            $actual = $this->existingColumns($table);

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
                        'backfill NULLs, then SET NOT NULL (not auto-applied)',
                    );
                } elseif (! $wantsRequired && ! $nullable) {
                    $diff->alterations[] = new SchemaAlteration(
                        $table,
                        $column,
                        'drop_not_null',
                        'ALTER COLUMN DROP NOT NULL',
                    );
                }
            }

            foreach ($actual as $column) {
                if ($column === 'id' || isset($expected[$column])) {
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
    public function apply(Registry $registry, array $modelClasses, ?SchemaDiff $diff = null): SchemaDiff
    {
        $diff ??= $this->compute($registry, $modelClasses);

        Registry::with($registry, function () use ($registry, $modelClasses, $diff): void {
            foreach ($diff->newTables as [, $modelClass]) {
                $this->builder->ensureTable($modelClass);
            }

            foreach ($diff->newColumns as [$table, $column, $field]) {
                if (in_array($column, $this->existingColumns($table), true)) {
                    continue;
                }

                $sql = $field->sqlType();
                $null = $field->required ? ' NOT NULL' : '';
                $default = $this->defaultSql($field);

                $this->connection->execute(
                    'ALTER TABLE "'.$table.'" ADD COLUMN "'.$column.'" '.$sql.$null.$default,
                );
            }

            foreach ($diff->alterations as $alteration) {
                if ($alteration->kind !== 'drop_not_null') {
                    continue;
                }

                if ($this->supportsDropNotNull()) {
                    $this->connection->execute(
                        'ALTER TABLE "'.$alteration->table.'" ALTER COLUMN "'.$alteration->column.'" DROP NOT NULL',
                    );
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

        return $diff;
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
            if ($field->name === 'display_name' || $field instanceof Many2manyField) {
                continue;
            }

            $columns[$field->column] = $field;
        }

        return $columns;
    }

    /**
     * @return list<string>
     */
    private function existingColumns(string $table): array
    {
        $rows = $this->pragmaTableInfo($table);

        if ($rows !== []) {
            return array_values(array_map(
                static fn (array $row): string => (string) ($row['name'] ?? ''),
                $rows,
            ));
        }

        if (! $this->supportsInformationSchema()) {
            return [];
        }

        $database = $this->currentDatabase();

        if ($database === null) {
            return [];
        }

        $rows = $this->connection->fetchAll(
            'SELECT column_name FROM information_schema.columns WHERE table_schema = ? AND table_name = ?',
            [$database, $table],
        );

        return array_values(array_map(
            static fn (array $row): string => (string) ($row['column_name'] ?? $row['COLUMN_NAME'] ?? ''),
            $rows,
        ));
    }

    private function columnIsNullable(string $table, string $column): bool
    {
        foreach ($this->pragmaTableInfo($table) as $row) {
            if ((string) ($row['name'] ?? '') === $column) {
                return (int) ($row['notnull'] ?? 1) === 0;
            }
        }

        if (! $this->supportsInformationSchema()) {
            return true;
        }

        $database = $this->currentDatabase();

        if ($database === null) {
            return true;
        }

        $rows = $this->connection->fetchAll(
            'SELECT is_nullable FROM information_schema.columns '
            .'WHERE table_schema = ? AND table_name = ? AND column_name = ?',
            [$database, $table, $column],
        );

        if ($rows === []) {
            return true;
        }

        $nullable = $rows[0]['is_nullable'] ?? $rows[0]['IS_NULLABLE'] ?? 'YES';

        return strtoupper((string) $nullable) === 'YES';
    }

    private function supportsDropNotNull(): bool
    {
        return $this->supportsInformationSchema();
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

    private function supportsInformationSchema(): bool
    {
        static $supported = null;

        if ($supported !== null) {
            return $supported;
        }

        try {
            $this->connection->fetchAll('SELECT DATABASE() as db');
            $supported = true;
        } catch (\Throwable) {
            $supported = false;
        }

        return $supported;
    }

    private function currentDatabase(): ?string
    {
        if (! $this->supportsInformationSchema()) {
            return null;
        }

        $driverRows = $this->connection->fetchAll('SELECT DATABASE() as db');
        $database = $driverRows[0]['db'] ?? null;

        return is_string($database) ? $database : null;
    }

    private function defaultSql(Field $field): string
    {
        if ($field->default === null) {
            return '';
        }

        $value = $field->toSql($field->default);

        if (is_bool($value)) {
            return ' DEFAULT '.($value ? '1' : '0');
        }

        if (is_int($value)) {
            return ' DEFAULT '.$value;
        }

        if (is_string($value)) {
            return " DEFAULT '".$value."'";
        }

        return '';
    }
}
