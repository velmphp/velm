<?php

declare(strict_types=1);

use Velm\Database\PdoConnection;
use Velm\Schema\SqlDialect;

test('sql dialect id column uses autoincrement on sqlite', function (): void {
    $dialect = SqlDialect::for(PdoConnection::sqliteMemory());

    expect($dialect->driver())->toBe('sqlite')
        ->and($dialect->idColumnSql())->toBe('"id" INTEGER PRIMARY KEY AUTOINCREMENT');
});

test('sql dialect id column uses serial on postgres', function (): void {
    $dialect = new SqlDialect('pgsql');

    expect($dialect->idColumnSql())->toBe('"id" SERIAL PRIMARY KEY');
});

test('sql dialect id column uses auto increment on mysql', function (): void {
    $dialect = new SqlDialect('mysql');

    expect($dialect->idColumnSql())->toBe('"id" BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY');
});

test('schema builder creates tables on sqlite with autoincrement id', function (): void {
    $connection = PdoConnection::sqliteMemory();
    $connection->execute(
        'CREATE TABLE IF NOT EXISTS "res_country" ('.SqlDialect::for($connection)->idColumnSql().', "name" TEXT NOT NULL)',
    );

    $connection->execute('INSERT INTO "res_country" ("name") VALUES (?)', ['Belgium']);
    $row = $connection->fetchOne('SELECT id, name FROM "res_country"');

    expect($row['id'])->toBe(1)
        ->and($row['name'])->toBe('Belgium');
});
