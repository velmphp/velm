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

test('registerExtension sets the effective model class to the latest extension', function (): void {
    Registry::using(function (Registry $registry): void {
        $registry->register(Country::class);
        $registry->registerExtension(CountryExtension::class);

        expect($registry->modelClass('res.country'))->toBe(CountryExtension::class)
            ->and($registry->baseModelClass('res.country'))->toBe(Country::class);
    });
});

test('extensions can extend Model and chain via static::super()', function (): void {
    Registry::using(function (Registry $registry): void {
        $registry->register(Country::class);
        $registry->registerExtension(CountryExtension::class);
        $registry->registerExtension(CountryTagExtension::class);

        expect(CountryTagExtension::displayNameFor([
            'name' => 'Belgium',
            'region_code' => 'EU',
            'tag' => 'benelux',
        ]))->toBe('Belgium [EU] #benelux')
            ->and($registry->superClass(CountryTagExtension::class))->toBe(CountryExtension::class)
            ->and($registry->superClass(CountryExtension::class))->toBe(Country::class)
            ->and($registry->superClass(Country::class))->toBeNull();
    });
});

test('orphan extensions that only extend Model are accepted', function (): void {
    Registry::using(function (Registry $registry): void {
        $registry->register(Country::class);
        $registry->registerExtension(OrphanCountryExtension::class);

        expect($registry->fieldSet('res.country'))->toHaveKey('orphan')
            ->and($registry->extensionsFor('res.country'))->toBe([OrphanCountryExtension::class]);
    });
});

test('extension chain preserves registration order', function (): void {
    Registry::using(function (Registry $registry): void {
        $registry->register(Country::class);
        $registry->registerExtension(CountryExtension::class);
        $registry->registerExtension(CountryTagExtension::class);

        expect($registry->extensionChainFor('res.country'))->toBe([
            Country::class,
            CountryExtension::class,
            CountryTagExtension::class,
        ]);
    });
});
