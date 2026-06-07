<?php

declare(strict_types=1);

use Velm\Core\Tests\Support\Country;
use Velm\Database\PdoConnection;
use Velm\Environment;
use Velm\Registry;
use Velm\Schema\LaravelSchema;
use Velm\Schema\SchemaBuilder;

test('laravel schema creates model tables with autoincrement id', function (): void {
    $connection = PdoConnection::sqliteMemory();
    $schema = LaravelSchema::for($connection);

    $schema->createModelTable('res_country', Country::fields());

    expect($schema->hasTable('res_country'))->toBeTrue()
        ->and($schema->columnListing('res_country'))->toContain('id', 'name', 'code');
});

test('schema builder syncs registry tables via laravel schema', function (): void {
    $connection = PdoConnection::sqliteMemory();
    $registry = Registry::using(function (Registry $registry): Registry {
        $registry->register(Country::class);

        return $registry;
    });

    (new SchemaBuilder($connection))->syncRegistry($registry);

    expect(LaravelSchema::for($connection)->hasTable('res_country'))->toBeTrue();
});
