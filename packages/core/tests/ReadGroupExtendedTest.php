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
