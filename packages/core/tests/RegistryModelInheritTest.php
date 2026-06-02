<?php

declare(strict_types=1);

use Velm\Core\Tests\Support\Country;
use Velm\Core\Tests\Support\CountryExtension;
use Velm\Core\Tests\Support\CountryTagExtension;
use Velm\Core\Tests\Support\OrphanCountryExtension;
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

test('registerExtension replaces the registry model class for parent:: overrides', function (): void {
    Registry::using(function (Registry $registry): void {
        $registry->register(Country::class);
        $registry->registerExtension(CountryExtension::class);

        expect($registry->modelClass('res.country'))->toBe(CountryExtension::class);
    });
});

test('displayNameFor on the extended class chains through parent::', function (): void {
    expect(CountryExtension::displayNameFor([
        'name' => 'Belgium',
        'code' => 'BE',
        'region_code' => 'EU',
    ]))->toBe('Belgium [EU]');
});

test('stacked extensions replace the model class and chain super() through the stack', function (): void {
    Registry::using(function (Registry $registry): void {
        $registry->register(Country::class);
        $registry->registerExtension(CountryExtension::class);
        $registry->registerExtension(CountryTagExtension::class);

        expect($registry->modelClass('res.country'))->toBe(CountryTagExtension::class)
            ->and(CountryTagExtension::displayNameFor([
                'name' => 'Belgium',
                'region_code' => 'EU',
                'tag' => 'benelux',
            ]))->toBe('Belgium [EU] #benelux');
    });
});

test('registerExtension requires the extension to subclass the current model class', function (): void {
    Registry::using(function (Registry $registry): void {
        $registry->register(Country::class);
        $registry->registerExtension(OrphanCountryExtension::class);
    });
})->throws(RuntimeException::class, 'must extend');
