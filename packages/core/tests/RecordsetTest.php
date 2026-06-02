<?php

declare(strict_types=1);

use Velm\Database\PdoConnection;
use Velm\Environment;
use Velm\Registry;
use Velm\Schema\SchemaBuilder;
use Velm\Core\Tests\Support\Country;
use Velm\Core\Tests\Support\Partner;

function ormEnvironment(): Environment
{
    return Registry::using(function (Registry $registry): Environment {
        $registry->register(Country::class);
        $registry->register(Partner::class);

        $connection = PdoConnection::sqliteMemory();
        $env = new Environment($connection, $registry);
        (new SchemaBuilder($connection))->syncRegistry($registry);

        return $env;
    });
}

test('creates and reads a partner record', function (): void {
    $env = ormEnvironment();

    $partner = $env->model('res.partner')->create([
        'name' => 'Acme Corp',
        'active' => true,
    ]);

    expect($partner->ids())->toBe([1])
        ->and($partner->read())->toBe([
            [
                'id' => 1,
                'name' => 'Acme Corp',
                'active' => true,
                'country_id' => null,
                'display_name' => 'Acme Corp',
            ],
        ]);
});

test('writes field values on existing records', function (): void {
    $env = ormEnvironment();
    $partner = $env->model('res.partner')->create(['name' => 'Before']);

    $partner->write(['name' => 'After']);

    expect($partner->read()[0]['name'])->toBe('After');
});

test('searches with simple domains', function (): void {
    $env = ormEnvironment();
    $env->model('res.partner')->create(['name' => 'Active Co', 'active' => true]);
    $env->model('res.partner')->create(['name' => 'Inactive Co', 'active' => false]);

    $active = $env->model('res.partner')->search([['active', '=', true]]);

    expect($active->count())->toBe(1)
        ->and($active->read()[0]['name'])->toBe('Active Co');
});

test('many2one stores foreign key', function (): void {
    $env = ormEnvironment();
    $us = $env->model('res.country')->create(['name' => 'United States', 'code' => 'US']);
    $partner = $env->model('res.partner')->create([
        'name' => 'Acme',
        'country_id' => $us->ids()[0],
    ]);

    expect($partner->read()[0]['country_id'])->toBe(1);
});

test('registry rejects duplicate model names', function (): void {
    Registry::using(function (Registry $registry): void {
        $registry->register(Country::class);

        expect(fn () => $registry->register(Country::class))
            ->toThrow(RuntimeException::class);
    });
});
