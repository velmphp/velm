<?php

declare(strict_types=1);

namespace Velm\Schema;

use Velm\Database\Connection;
use Velm\Fields\Many2manyField;
use Velm\Fields\One2manyField;
use Velm\Models\Model;
use Velm\Registry;

final class SchemaBuilder
{
    private readonly LaravelSchema $schema;

    public function __construct(
        Connection $connection,
    ) {
        $this->schema = LaravelSchema::for($connection);
    }

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
                $this->schema->createMany2manyTable($relation, $col1, $col2);
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
        $fields = [];

        foreach ($modelClass::fields() as $field) {
            if ($field->name === 'id' || ! $field->persistsInDatabase()) {
                continue;
            }

            $fields[] = $field;
        }

        $this->schema->createModelTable($modelClass::table(), $fields);
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    public function applyColumnDiff(string $modelClass): void
    {
        $table = $modelClass::table();

        if ($this->schema->columnListing($table) === []) {
            return;
        }

        foreach ($modelClass::fields() as $field) {
            if ($field->name === 'id' || ! $field->persistsInDatabase()) {
                continue;
            }

            $this->schema->addFieldColumn($table, $field);
        }
    }
}
