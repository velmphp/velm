<?php

declare(strict_types=1);

namespace Velm\Schema;

use Velm\Database\Connection;
use Velm\Fields\Field;
use Velm\Fields\Many2manyField;
use Velm\Fields\One2manyField;
use Velm\Models\Model;
use Velm\Registry;

final class SchemaBuilder
{
    public function __construct(
        private readonly Connection $connection,
    ) {}

    public function syncRegistry(Registry $registry): void
    {
        foreach ($registry->models() as $modelClass) {
            $this->ensureTable($modelClass);
        }

        $this->ensureRelationTables($registry);
    }

    public function ensureRelationTables(Registry $registry): void
    {
        /** @var array<string, true> $created */
        $created = [];

        foreach ($registry->models() as $modelClass) {
            foreach ($modelClass::fields() as $field) {
                if (! $field instanceof Many2manyField) {
                    continue;
                }

                [$relation] = $field->resolveSpec($modelClass, $registry);

                if (isset($created[$relation])) {
                    continue;
                }

                [$relation, $col1, $col2] = $field->resolveSpec($modelClass, $registry);
                $this->connection->execute(
                    'CREATE TABLE IF NOT EXISTS "'.$relation.'" ('
                    .'"'.$col1.'" INTEGER NOT NULL, "'.$col2.'" INTEGER NOT NULL, '
                    .'PRIMARY KEY ("'.$col1.'", "'.$col2.'"))',
                );
                $created[$relation] = true;
            }
        }
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    public function ensureTable(string $modelClass): void
    {
        $this->createTable($modelClass);
        $this->applyColumnDiff($modelClass);
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    public function createTable(string $modelClass): void
    {
        $columns = ['"id" INTEGER PRIMARY KEY AUTOINCREMENT'];

        foreach ($modelClass::fields() as $field) {
            if ($field->name === 'id' || $field->name === 'display_name' || $field instanceof Many2manyField || $field instanceof One2manyField) {
                continue;
            }

            $sql = $field->sqlType();
            $null = $field->required ? ' NOT NULL' : '';
            $default = $this->defaultSql($field);
            $columns[] = '"'.$field->column.'" '.$sql.$null.$default;
        }

        $table = $modelClass::table();
        $this->connection->execute(
            'CREATE TABLE IF NOT EXISTS "'.$table.'" ('.implode(', ', $columns).')',
        );
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    public function applyColumnDiff(string $modelClass): void
    {
        $table = $modelClass::table();
        $existing = $this->existingColumns($table);

        if ($existing === []) {
            return;
        }

        foreach ($modelClass::fields() as $field) {
            if ($field->name === 'id' || $field->name === 'display_name' || $field instanceof Many2manyField || $field instanceof One2manyField) {
                continue;
            }

            if (in_array($field->column, $existing, true)) {
                continue;
            }

            $sql = $field->sqlType();
            $null = $field->required ? ' NOT NULL' : '';
            $default = $this->defaultSql($field);

            $this->connection->execute(
                'ALTER TABLE "'.$table.'" ADD COLUMN "'.$field->column.'" '.$sql.$null.$default,
            );
        }
    }

    /**
     * @return list<string>
     */
    private function existingColumns(string $table): array
    {
        $rows = $this->connection->fetchAll('PRAGMA table_info("'.$table.'")');

        if ($rows !== []) {
            return array_values(array_map(
                static fn (array $row): string => (string) $row['name'],
                $rows,
            ));
        }

        $driverRows = $this->connection->fetchAll('SELECT DATABASE() as db');
        $database = $driverRows[0]['db'] ?? null;

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
