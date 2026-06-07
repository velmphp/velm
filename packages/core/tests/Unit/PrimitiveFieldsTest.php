<?php

declare(strict_types=1);

use Velm\Fields\BooleanField;
use Velm\Fields\IntegerField;
use Velm\Fields\TextField;

test('boolean field converts values for php and sql', function (): void {
    $field = BooleanField::make('Active', default: true, required: true);

    expect($field->sqlType())->toBe('INTEGER')
        ->and($field->toPhp(null))->toBeNull()
        ->and($field->toPhp(1))->toBeTrue()
        ->and($field->toSql(null))->toBeNull()
        ->and($field->toSql(true))->toBe(1)
        ->and($field->toSql(false))->toBe(0);
});

test('integer field converts sql type and php values', function (): void {
    $field = IntegerField::make('Qty', default: 0, required: true, readonly: true);

    expect($field->sqlType())->toBe('INTEGER')
        ->and($field->toPhp(null))->toBeNull()
        ->and($field->toPhp('12'))->toBe(12);
});

test('text field converts sql type and php values', function (): void {
    $field = TextField::make('Notes', default: 'n/a', required: false, readonly: true);

    expect($field->sqlType())->toBe('TEXT')
        ->and($field->toPhp(null))->toBeNull()
        ->and($field->toPhp(123))->toBe('123');
});
