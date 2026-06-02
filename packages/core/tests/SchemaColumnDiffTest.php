<?php

declare(strict_types=1);

use Velm\Core\Tests\Support\Country;
use Velm\Database\PdoConnection;
use Velm\Registry;
use Velm\Schema\SchemaBuilder;

test('applyColumnDiff adds new columns to an existing table', function (): void {
    $pdo = new PDO('sqlite::memory:');
    $connection = new PdoConnection($pdo);
    $schema = new SchemaBuilder($connection);

    Country::initialize();
    $connection->execute(
        'CREATE TABLE "res_country" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "name" TEXT NOT NULL)',
    );

    $schema->applyColumnDiff(Country::class);

    $rows = $connection->fetchAll('PRAGMA table_info("res_country")');
    $columns = array_column($rows, 'name');

    expect($columns)->toContain('code');
});
