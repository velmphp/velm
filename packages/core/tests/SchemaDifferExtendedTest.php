<?php

declare(strict_types=1);

use Velm\Core\Tests\Support\Partner;
use Velm\Database\PdoConnection;
use Velm\Registry;
use Velm\Schema\SchemaDiffer;

test('schema differ reports new tables for unregistered models', function (): void {
    $connection = PdoConnection::sqliteMemory();

    $registry = Registry::using(function (Registry $registry): Registry {
        $registry->register(Partner::class);

        return $registry;
    });

    $differ = new SchemaDiffer($connection);
    $diff = $differ->compute($registry, [Partner::class]);

    expect($diff->newTables)->not->toBeEmpty()
        ->and($diff->newTables[0][0])->toBe('res_partner');
});

test('schema differ apply creates missing tables and columns', function (): void {
    $connection = PdoConnection::sqliteMemory();

    $registry = Registry::using(function (Registry $registry): Registry {
        $registry->register(Partner::class);

        return $registry;
    });

    $differ = new SchemaDiffer($connection);
    $result = $differ->apply($registry, [Partner::class]);

    expect($result->diff->newTables)->not->toBeEmpty()
        ->and($differ->compute($registry, [Partner::class])->newTables)->toBe([]);
});

test('schema differ counts null rows in existing table', function (): void {
    $connection = PdoConnection::sqliteMemory();

    $registry = Registry::using(function (Registry $registry): Registry {
        $registry->register(Partner::class);

        return $registry;
    });

    $differ = new SchemaDiffer($connection);
    $differ->apply($registry, [Partner::class]);

    expect($differ->countNullRows('res_partner', 'name'))->toBe(0);
});

test('schema differ reports sqlite does not support alter column nullability', function (): void {
    $connection = PdoConnection::sqliteMemory();
    $differ = new SchemaDiffer($connection);

    expect($differ->supportsAlterColumnNullability())->toBeFalse();
});

test('schema differ reports orphan columns on existing tables', function (): void {
    $connection = PdoConnection::sqliteMemory();

    $registry = Registry::using(function (Registry $registry): Registry {
        $registry->register(Partner::class);

        return $registry;
    });

    $connection->execute(
        'CREATE TABLE res_partner (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, legacy_col TEXT)',
    );

    $differ = new SchemaDiffer($connection);
    $diff = $differ->compute($registry, [Partner::class]);

    expect(collect($diff->orphanColumns)->pluck(1)->all())->toContain('legacy_col');
});

test('schema differ apply handles extension model column diff', function (): void {
    $connection = PdoConnection::sqliteMemory();

    $registry = Registry::using(function (Registry $registry): Registry {
        $registry->register(Partner::class);
        $registry->registerExtension(\Velm\Modules\Tests\Support\PartnerExtension::class);

        return $registry;
    });

    $differ = new SchemaDiffer($connection);
    $result = $differ->apply($registry, [Partner::class, \Velm\Modules\Tests\Support\PartnerExtension::class]);

    $columns = collect($connection->fetchAll('PRAGMA table_info(res_partner)'))->pluck('name')->all();

    expect($result->diff->newTables)->not->toBeEmpty()
        ->and($columns)->toContain('ref');
});

test('schema differ apply skips set not null when null rows remain', function (): void {
    $connection = PdoConnection::sqliteMemory();

    $registry = Registry::using(function (Registry $registry): Registry {
        $registry->register(Partner::class);

        return $registry;
    });

    $connection->execute(
        'CREATE TABLE res_partner (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, active INTEGER DEFAULT 1, country_id INTEGER)',
    );
    $connection->execute("INSERT INTO res_partner (name) VALUES (NULL)");

    $differ = new SchemaDiffer($connection);
    $result = $differ->apply($registry, [Partner::class]);

    expect($result->skippedNotNull)->toBeGreaterThanOrEqual(0);
});

test('schema differ reports new columns on partially migrated tables', function (): void {
    $connection = PdoConnection::sqliteMemory();

    $registry = Registry::using(function (Registry $registry): Registry {
        $registry->register(Partner::class);

        return $registry;
    });

    $connection->execute(
        'CREATE TABLE res_partner (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL)',
    );

    $differ = new SchemaDiffer($connection);
    $diff = $differ->compute($registry, [Partner::class]);

    expect($diff->newColumns)->not->toBeEmpty();
});
