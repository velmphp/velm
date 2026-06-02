<?php

declare(strict_types=1);

use Velm\Core\Tests\Support\Country;
use Velm\Core\Tests\Support\CountryExtension;
use Velm\Registry;

test('registerExtension merges fields onto the inherited model', function (): void {
    Registry::using(function (Registry $registry): void {
        $registry->register(Country::class);
        $registry->registerExtension(CountryExtension::class);

        $fields = $registry->fieldSet('res.country');

        expect($fields)->toHaveKeys(['name', 'code', 'region_code', 'id', 'display_name'])
            ->and($fields['region_code']->required)->toBeFalse();
    });
});

test('model fields() returns merged fields when a registry is active', function (): void {
    Registry::using(function (Registry $registry): void {
        $registry->register(Country::class);
        $registry->registerExtension(CountryExtension::class);

        expect(Country::fields())->toHaveKey('region_code');
    });
});

test('registerExtension fails when the target model is not loaded', function (): void {
    Registry::using(function (Registry $registry): void {
        $registry->registerExtension(CountryExtension::class);
    });
})->throws(RuntimeException::class, 'not registered');
