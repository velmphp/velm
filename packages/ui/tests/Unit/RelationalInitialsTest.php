<?php

declare(strict_types=1);

use Velm\Core\Tests\Support\Order;
use Velm\Core\Tests\Support\OrderLine;
use Velm\Core\Tests\Support\Tag;
use Velm\Database\PdoConnection;
use Velm\Environment;
use Velm\Fields\Many2manyField;
use Velm\Fields\One2manyField;
use Velm\Registry;
use Velm\Schema\SchemaBuilder;
use Velm\Ui\Support\RelationalInitials;

test('relational initials normalizeIds filters empty values', function (): void {
    expect(RelationalInitials::normalizeIds([1, '', null, 3]))->toBe([1, 3])
        ->and(RelationalInitials::normalizeIds('not-array'))->toBe([]);
});

test('many2many chips resolve display names', function (): void {
    $env = Registry::using(function (Registry $registry): Environment {
        $registry->register(Tag::class);
        $connection = PdoConnection::sqliteMemory();
        (new SchemaBuilder($connection))->syncRegistry($registry);

        return new Environment($connection, $registry);
    });

    $tagA = $env->model('test.tag')->create(['name' => 'Alpha'])->ids()[0];
    $tagB = $env->model('test.tag')->create(['name' => 'Beta'])->ids()[0];

    $field = Many2manyField::make('test.tag');
    $chips = RelationalInitials::many2manyChips($env, $field, [$tagA, $tagB]);

    expect($chips)->toHaveCount(2)
        ->and($chips[0]['label'])->toBe('Alpha')
        ->and($chips[1]['label'])->toBe('Beta');
});

test('one2many rows include column values', function (): void {
    $env = Registry::using(function (Registry $registry): Environment {
        $registry->register(OrderLine::class);
        $registry->register(Order::class);
        $connection = PdoConnection::sqliteMemory();
        (new SchemaBuilder($connection))->syncRegistry($registry);

        return new Environment($connection, $registry);
    });

    $orderId = $env->model('test.order')->create(['name' => 'SO1'])->ids()[0];
    $lineId = $env->model('test.order.line')->create([
        'order_id' => $orderId,
        'description' => 'Widget',
    ])->ids()[0];

    $field = One2manyField::make('test.order.line', 'order_id');
    $rows = RelationalInitials::one2manyRows($env, $field, [$lineId], [
        ['name' => 'description', 'label' => 'Description'],
    ]);

    expect($rows)->toHaveCount(1)
        ->and($rows[0]['description'])->toBe('Widget');
});

