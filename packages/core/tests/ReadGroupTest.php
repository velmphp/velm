<?php

declare(strict_types=1);

use Velm\Core\Tests\Support\Country;
use Velm\Core\Tests\Support\SaleOrder;
use Velm\Core\Tests\Support\Partner;
use Velm\Database\PdoConnection;
use Velm\Environment;
use Velm\Registry;
use Velm\Schema\SchemaBuilder;

function readGroupEnvironment(): Environment
{
    return Registry::using(function (Registry $registry): Environment {
        $registry->register(Country::class);
        $registry->register(Partner::class);
        $registry->register(SaleOrder::class);

        $connection = PdoConnection::sqliteMemory();
        $env = new Environment($connection, $registry);
        (new SchemaBuilder($connection))->syncRegistry($registry);

        return $env;
    });
}

test('readGroup groups by scalar field with counts', function (): void {
    $env = readGroupEnvironment();
    $model = $env->model('sale.order');

    $model->create(['name' => 'A', 'state' => 'draft', 'amount' => 10]);
    $model->create(['name' => 'B', 'state' => 'draft', 'amount' => 20]);
    $model->create(['name' => 'C', 'state' => 'done', 'amount' => 30]);

    $groups = $model->readGroup([], ['amount:sum'], ['state']);

    expect($groups)->toHaveCount(2);

    $byState = collect($groups)->keyBy('state');

    expect($byState['draft']['__count'])->toBe(2)
        ->and($byState['draft']['amount_sum'])->toBe(30)
        ->and($byState['draft']['state_count'])->toBe(2)
        ->and($byState['done']['__count'])->toBe(1)
        ->and($byState['done']['amount_sum'])->toBe(30)
        ->and($byState['done']['__domain'])->toBe([['state', '=', 'done']]);
});

test('readGroup groups by many2one id', function (): void {
    $env = readGroupEnvironment();
    $us = $env->model('res.country')->create(['name' => 'United States', 'code' => 'US']);
    $ca = $env->model('res.country')->create(['name' => 'Canada', 'code' => 'CA']);

    $env->model('sale.order')->create(['name' => 'US-1', 'country_id' => $us->ids()[0], 'amount' => 5]);
    $env->model('sale.order')->create(['name' => 'US-2', 'country_id' => $us->ids()[0], 'amount' => 15]);
    $env->model('sale.order')->create(['name' => 'CA-1', 'country_id' => $ca->ids()[0], 'amount' => 7]);

    $groups = $env->model('sale.order')->readGroup([], ['amount:sum'], ['country_id']);
    $byCountry = collect($groups)->keyBy('country_id');

    expect($byCountry[$us->ids()[0]]['__count'])->toBe(2)
        ->and($byCountry[$us->ids()[0]]['amount_sum'])->toBe(20)
        ->and($byCountry[$ca->ids()[0]]['amount_sum'])->toBe(7);
});

test('readGroup applies domain filter', function (): void {
    $env = readGroupEnvironment();

    $env->model('sale.order')->create(['name' => 'A', 'state' => 'draft', 'amount' => 10]);
    $env->model('sale.order')->create(['name' => 'B', 'state' => 'done', 'amount' => 20]);

    $groups = $env->model('sale.order')->readGroup(
        [['state', '=', 'draft']],
        ['amount:sum'],
        ['state'],
    );

    expect($groups)->toHaveCount(1)
        ->and($groups[0]['state'])->toBe('draft')
        ->and($groups[0]['amount_sum'])->toBe(10);
});

test('readGroup supports avg aggregate', function (): void {
    $env = readGroupEnvironment();

    $env->model('sale.order')->create(['name' => 'A', 'state' => 'draft', 'amount' => 10]);
    $env->model('sale.order')->create(['name' => 'B', 'state' => 'draft', 'amount' => 30]);

    $groups = $env->model('sale.order')->readGroup([], ['amount:avg'], ['state']);

    expect($groups[0]['amount_avg'])->toBe(20.0);
});
