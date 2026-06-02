<?php

declare(strict_types=1);

use Velm\Core\Tests\Support\Country;
use Velm\Database\PdoConnection;
use Velm\Registry;
use Velm\Schema\SchemaDiffer;

test('schema differ reports and applies new columns', function (): void {
    $connection = PdoConnection::sqliteMemory();
    $connection->execute(
        'CREATE TABLE "res_country" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "name" TEXT NOT NULL)',
    );

    $registry = Registry::using(function (Registry $registry): Registry {
        $registry->register(Country::class);

        return $registry;
    });

    $differ = new SchemaDiffer($connection);
    $diff = $differ->compute($registry, [Country::class]);

    expect($diff->newColumns)->toHaveCount(1)
        ->and($diff->newColumns[0][1])->toBe('code');

    $differ->apply($registry, [Country::class], $diff);

    $columns = array_column($connection->fetchAll('PRAGMA table_info("res_country")'), 'name');

    expect($columns)->toContain('code');
});

test('schema differ reports orphan columns', function (): void {
    $connection = PdoConnection::sqliteMemory();
    $connection->execute(
        'CREATE TABLE "res_country" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "name" TEXT NOT NULL, "legacy" TEXT)',
    );

    $registry = Registry::using(function (Registry $registry): Registry {
        $registry->register(Country::class);

        return $registry;
    });

    $diff = (new SchemaDiffer($connection))->compute($registry, [Country::class]);

    expect($diff->orphanColumns)->toBe([['res_country', 'legacy']]);
});

test('schema differ counts null rows for set_not_null columns', function (): void {
    $connection = PdoConnection::sqliteMemory();
    $connection->execute(
        'CREATE TABLE "res_country" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "name" TEXT NOT NULL, "code" TEXT)',
    );
    $connection->execute('INSERT INTO "res_country" ("name", "code") VALUES (\'France\', NULL)');

    $differ = new SchemaDiffer($connection);

    expect($differ->countNullRows('res_country', 'code'))->toBe(1);
});
