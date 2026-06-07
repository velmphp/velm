<?php

declare(strict_types=1);

use Velm\Core\Tests\Support\BadOrder;
use Velm\Core\Tests\Support\Country;
use Velm\Core\Tests\Support\Order;
use Velm\Core\Tests\Support\OrderLine;
use Velm\Core\Tests\Support\WrongTargetLine;
use Velm\Core\Tests\Support\WrongTargetOrder;
use Velm\Fields\One2manyField;
use Velm\Registry;

test('one2many field fluent configuration', function (): void {
    $field = One2manyField::make('test.order.line', 'order_id')
        ->comodel('test.order.line')
        ->inverse('order_id')
        ->listView('order.line.list')
        ->formView('order.line.form')
        ->bind('line_ids');

    expect($field->comodel)->toBe('test.order.line')
        ->and($field->inverseName)->toBe('order_id')
        ->and($field->listView)->toBe('order.line.list')
        ->and($field->formView)->toBe('order.line.form')
        ->and($field->persistsInDatabase())->toBeFalse();
});

test('one2many field rejects sql column access', function (): void {
    $field = One2manyField::make('test.order.line', 'order_id');

    expect(fn () => $field->sqlType())->toThrow(LogicException::class)
        ->and(fn () => $field->toSql([]))->toThrow(LogicException::class);
});

test('one2many validateInverse requires comodel and inverse', function (): void {
    $registry = Registry::using(fn (Registry $registry): Registry => $registry);
    $field = One2manyField::make()->bind('line_ids');

    expect(fn () => $field->validateInverse(Order::class, $registry))
        ->toThrow(LogicException::class, 'requires comodel() and inverse()');
});

test('one2many validateInverse requires registered comodel', function (): void {
    $registry = Registry::using(fn (Registry $registry): Registry => $registry);
    $field = One2manyField::make('missing.model', 'parent_id')->bind('line_ids');

    expect(fn () => $field->validateInverse(Order::class, $registry))
        ->toThrow(RuntimeException::class, 'comodel missing.model is not registered');
});

test('one2many validateInverse requires many2one inverse', function (): void {
    $registry = Registry::using(function (Registry $registry): Registry {
        $registry->register(OrderLine::class);

        return $registry;
    });
    $field = One2manyField::make('test.order.line', 'description')->bind('line_ids');

    expect(fn () => $field->validateInverse(BadOrder::class, $registry))
        ->toThrow(RuntimeException::class, 'must be a Many2one');
});

test('one2many validateInverse requires inverse to point at parent model', function (): void {
    expect(fn () => Registry::using(function (Registry $registry): void {
        $registry->register(Country::class);
        $registry->register(WrongTargetLine::class);
        $registry->register(WrongTargetOrder::class);
    }))->toThrow(RuntimeException::class, 'must point at test.wrong.order');
});
