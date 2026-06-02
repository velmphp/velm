<?php

declare(strict_types=1);

namespace Velm\Schema;

use Velm\Database\Connection;
use Velm\Fields\Field;
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
            $this->createTable($modelClass);
        }
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    public function createTable(string $modelClass): void
    {
        $columns = ['"id" INTEGER PRIMARY KEY AUTOINCREMENT'];

        foreach ($modelClass::fields() as $field) {
            if ($field->name === 'id' || $field->name === 'display_name') {
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
