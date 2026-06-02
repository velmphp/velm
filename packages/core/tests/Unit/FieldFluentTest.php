<?php

declare(strict_types=1);

use Velm\Fields\CharField;
use Velm\Fields\Many2oneField;

test('field fluent setters configure the same as make arguments', function () {
    $fluent = CharField::make()
        ->label('Name')
        ->default('Acme')
        ->required()
        ->readonly()
        ->maxLength(64)
        ->column('partner_name');

    $positional = CharField::make('Name', 'Acme', required: true, readonly: true, maxLength: 64)
        ->column('partner_name');

    expect($fluent->string)->toBe($positional->string)
        ->and($fluent->default)->toBe($positional->default)
        ->and($fluent->required)->toBeTrue()
        ->and($fluent->readonly)->toBeTrue()
        ->and($fluent->maxLength)->toBe(64)
        ->and($fluent->column)->toBe('partner_name');
});

test('many2one comodel can be set fluently or via make', function () {
    expect(Many2oneField::make()->comodel('res.partner')->comodel)->toBe('res.partner')
        ->and(Many2oneField::make('res.country')->comodel)->toBe('res.country');
});

test('bind preserves fluent configuration', function () {
    $field = CharField::make()->required()->maxLength(2)->bind('code');

    expect($field->name)->toBe('code')
        ->and($field->column)->toBe('code')
        ->and($field->required)->toBeTrue()
        ->and($field->maxLength)->toBe(2)
        ->and($field->sqlType())->toBe('VARCHAR(2)');
});

test('many2one without comodel throws on sqlType', function () {
    Many2oneField::make()->sqlType();
})->throws(LogicException::class);
