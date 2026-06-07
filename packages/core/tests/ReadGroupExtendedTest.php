<?php

declare(strict_types=1);

use Velm\Core\Tests\Support\Country;
use Velm\Core\Tests\Support\Partner;
use Velm\Core\Tests\Support\SaleOrder;
use Velm\Database\PdoConnection;
use Velm\Environment;
use Velm\Registry;
use Velm\Schema\SchemaBuilder;

test('readGroup groups by boolean field', function (): void {
    $env = Registry::using(function (Registry $registry): Environment {
        $registry->register(Country::class);
        $registry->register(Partner::class);
        $connection = PdoConnection::sqliteMemory();
        (new SchemaBuilder($connection))->syncRegistry($registry);

        return new Environment($connection, $registry);
    });

    $env->model('res.partner')->create(['name' => 'Active Co', 'active' => true]);
    $env->model('res.partner')->create(['name' => 'Inactive Co', 'active' => false]);
    $env->model('res.partner')->create(['name' => 'Also Active', 'active' => true]);

    $groups = $env->model('res.partner')->readGroup([], [], ['active']);
    $byActive = collect($groups)->keyBy(fn (array $g): string => $g['active'] ? '1' : '0');

    expect($byActive['1']['__count'])->toBe(2)
        ->and($byActive['0']['__count'])->toBe(1);
});

test('readGroup rejects empty groupby list', function (): void {
    $env = Registry::using(function (Registry $registry): Environment {
        $registry->register(SaleOrder::class);
        $connection = PdoConnection::sqliteMemory();
        (new SchemaBuilder($connection))->syncRegistry($registry);

        return new Environment($connection, $registry);
    });

    expect(fn () => $env->model('sale.order')->readGroup([], [], []))
        ->toThrow(InvalidArgumentException::class);
});

test('readGroup groups by many2one field', function (): void {
    $env = Registry::using(function (Registry $registry): Environment {
        $registry->register(Country::class);
        $registry->register(Partner::class);
        $connection = PdoConnection::sqliteMemory();
        (new SchemaBuilder($connection))->syncRegistry($registry);

        return new Environment($connection, $registry);
    });

    $be = $env->model('res.country')->create(['name' => 'Belgium', 'code' => 'BE'])->ids()[0];
    $nl = $env->model('res.country')->create(['name' => 'Netherlands', 'code' => 'NL'])->ids()[0];

    $env->model('res.partner')->create(['name' => 'BE 1', 'country_id' => $be]);
    $env->model('res.partner')->create(['name' => 'BE 2', 'country_id' => $be]);
    $env->model('res.partner')->create(['name' => 'NL 1', 'country_id' => $nl]);

    $groups = $env->model('res.partner')->readGroup([], [], ['country_id']);
    $counts = collect($groups)->mapWithKeys(fn (array $g): array => [
        (int) ($g['country_id'] ?? 0) => $g['__count'],
    ]);

    expect($counts[$be])->toBe(2)
        ->and($counts[$nl])->toBe(1);
});

test('readGroup aggregates integer sum', function (): void {
    $env = Registry::using(function (Registry $registry): Environment {
        $registry->register(SaleOrder::class);
        $connection = PdoConnection::sqliteMemory();
        (new SchemaBuilder($connection))->syncRegistry($registry);

        return new Environment($connection, $registry);
    });

    $env->model('sale.order')->create(['name' => 'SO1', 'amount' => 10]);
    $env->model('sale.order')->create(['name' => 'SO2', 'amount' => 25]);

    $groups = $env->model('sale.order')->readGroup([], ['amount:sum'], ['state']);

    expect($groups[0]['amount_sum'])->toBe(35);
});

test('readGroup supports avg min and max aggregates', function (): void {
    $env = Registry::using(function (Registry $registry): Environment {
        $registry->register(SaleOrder::class);
        $connection = PdoConnection::sqliteMemory();
        (new SchemaBuilder($connection))->syncRegistry($registry);

        return new Environment($connection, $registry);
    });

    $env->model('sale.order')->create(['name' => 'SO1', 'amount' => 10]);
    $env->model('sale.order')->create(['name' => 'SO2', 'amount' => 30]);

    $groups = $env->model('sale.order')->readGroup([], ['amount:avg', 'amount:min', 'amount:max'], ['state']);

    expect($groups[0]['amount_avg'])->toBe(20.0)
        ->and($groups[0]['amount_min'])->toBe(10)
        ->and($groups[0]['amount_max'])->toBe(30);
});

test('readGroup rejects malformed aggregate field syntax', function (): void {
    $env = Registry::using(function (Registry $registry): Environment {
        $registry->register(SaleOrder::class);
        $connection = PdoConnection::sqliteMemory();
        (new SchemaBuilder($connection))->syncRegistry($registry);

        return new Environment($connection, $registry);
    });

    expect(fn () => $env->model('sale.order')->readGroup([], ['amount'], ['state']))
        ->toThrow(InvalidArgumentException::class);
});

test('readGroup applies domain filter to grouped results', function (): void {
    $env = Registry::using(function (Registry $registry): Environment {
        $registry->register(SaleOrder::class);
        $connection = PdoConnection::sqliteMemory();
        (new SchemaBuilder($connection))->syncRegistry($registry);

        return new Environment($connection, $registry);
    });

    $env->model('sale.order')->create(['name' => 'Big', 'amount' => 100]);
    $env->model('sale.order')->create(['name' => 'Small', 'amount' => 5]);

    $groups = $env->model('sale.order')->readGroup([['amount', '>', 50]], ['amount:sum'], ['state']);

    expect($groups[0]['amount_sum'])->toBe(100);
});
