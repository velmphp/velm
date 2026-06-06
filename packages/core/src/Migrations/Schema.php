<?php

declare(strict_types=1);

namespace Velm\Migrations;

use Velm\Environment;
use Velm\Schema\LaravelSchema;

final class Schema
{
    private readonly LaravelSchema $schema;

    public function __construct(
        Environment $env,
    ) {
        $this->schema = LaravelSchema::for($env->connection);
    }

    public static function make(Environment $env): self
    {
        return new self($env);
    }

    /**
     * @param  callable(Table): void  $blueprint
     */
    public function create(string $table, callable $blueprint): void
    {
        $definition = new Table;
        $blueprint($definition);

        $this->schema->createMigrationTable($table, $definition->columns());
    }

    /**
     * @param  callable(Table): void  $blueprint
     */
    public function table(string $table, callable $blueprint): void
    {
        if (! $this->schema->hasTable($table)) {
            throw new \RuntimeException("Table {$table} does not exist.");
        }

        $definition = new Table;
        $blueprint($definition);

        foreach ($definition->columns() as $column) {
            $this->schema->addMigrationColumn($table, $column);
        }
    }
}
