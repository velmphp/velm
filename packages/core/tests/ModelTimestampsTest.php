<?php

declare(strict_types=1);

use Velm\Core\Tests\Support\Country;
use Velm\Core\Tests\Support\Partner;
use Velm\Fields\DatetimeField;

test('base models include readonly timestamp fields', function (): void {
    Partner::initialize();

    $fields = Partner::baseFields();

    expect($fields)->toHaveKeys(['created_at', 'updated_at'])
        ->and($fields['created_at'])->toBeInstanceOf(DatetimeField::class)
        ->and($fields['created_at']->readonly)->toBeTrue()
        ->and(Partner::usesTimestamps())->toBeTrue();
});

test('extensions do not add duplicate timestamp fields', function (): void {
    Country::initialize();

    expect(Country::usesTimestamps())->toBeTrue()
        ->and(Country::baseFields())->toHaveKeys(['created_at', 'updated_at']);
});
