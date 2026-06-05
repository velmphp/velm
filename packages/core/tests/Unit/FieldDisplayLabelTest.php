<?php

declare(strict_types=1);

use Velm\Fields\CharField;
use Velm\Fields\Field;
use Velm\Fields\Many2oneField;

test('displayLabel returns explicit string label', function (): void {
    $field = Many2oneField::make('res.company')->label('Company')->bind('company_id');

    expect($field->displayLabel())->toBe('Company');
});

test('displayLabel humanizes many2one field names without labels', function (): void {
    $field = Many2oneField::make('res.country')->bind('country_id');

    expect($field->displayLabel())->toBe('Country');
});

test('humanizeFieldName strips _id suffix and title-cases', function (): void {
    expect(Field::humanizeFieldName('company_id'))->toBe('Company')
        ->and(Field::humanizeFieldName('requester_id'))->toBe('Requester')
        ->and(CharField::make()->bind('change_type')->displayLabel())->toBe('Change Type');
});
