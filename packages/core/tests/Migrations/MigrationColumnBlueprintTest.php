<?php

declare(strict_types=1);

use Velm\Database\PdoConnection;
use Velm\Migrations\ColumnDefinition;
use Velm\Migrations\MigrationColumnBlueprint;
use Velm\Schema\LaravelSchema;
use Illuminate\Database\Schema\Blueprint;

test('migration column blueprint creates supported column types', function (): void {
    $connection = PdoConnection::sqliteMemory();
    $schema = LaravelSchema::for($connection);

    $schema->createMigrationTable('migration_blueprint_test', [
        new ColumnDefinition('body', 'TEXT', true),
        new ColumnDefinition('qty', 'INTEGER', false),
        new ColumnDefinition('active', 'BOOLEAN', true, 0),
        new ColumnDefinition('code', 'VARCHAR(8)', true),
    ]);

    expect($schema->columnListing('migration_blueprint_test'))
        ->toContain('body', 'qty', 'active', 'code');
});

test('migration column blueprint rejects unsupported sql types', function (): void {
    $connection = PdoConnection::sqliteMemory();
    LaravelSchema::for($connection)->createMigrationTable('grammar_init', []);
    $blueprint = new Blueprint($connection->illuminateConnection(), 'bad_column_test');

    expect(fn () => MigrationColumnBlueprint::addColumn(
        $blueprint,
        new ColumnDefinition('x', 'UNSUPPORTED', true),
    ))->toThrow(InvalidArgumentException::class, 'Unsupported migration column type');
});
