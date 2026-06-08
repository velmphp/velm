<?php

declare(strict_types=1);

use Velm\Fields\FloatField;

test('float field exposes real sql type and casts values', function (): void {
    $field = FloatField::make();

    expect($field->sqlType())->toBe('REAL')
        ->and($field->toPhp(null))->toBeNull()
        ->and($field->toPhp('12.5'))->toBe(12.5)
        ->and($field->toPhp(3))->toBe(3.0);
});
