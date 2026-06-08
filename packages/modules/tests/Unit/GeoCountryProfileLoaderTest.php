<?php

declare(strict_types=1);

use Velm\Modules\GeoData\GeoCountryProfileLoader;
use Velm\Modules\GeoData\GeoHttpGateway;
use Velm\Modules\Tests\TestCase;

uses(TestCase::class);

test('geo country profile loader maps restcountries rows and helper accessors', function (): void {
    $profile = GeoCountryProfileLoader::fromRestCountriesRow([
        'cca2' => 'be',
        'cca3' => 'bel',
        'name' => ['common' => 'Belgium'],
        'capital' => ['Brussels'],
        'population' => 11000000,
        'continents' => ['Europe'],
        'idd' => ['root' => '+3', 'suffixes' => ['2']],
        'currencies' => ['EUR' => ['name' => 'Euro']],
        'flag' => '🇧🇪',
    ]);

    expect($profile['code'])->toBe('BE')
        ->and($profile['name'])->toBe('Belgium')
        ->and($profile['_currency_code'])->toBe('EUR')
        ->and(GeoCountryProfileLoader::phoneCode([
            'idd' => ['root' => '+3', 'suffixes' => ['2']],
        ]))->toBe('32')
        ->and(GeoCountryProfileLoader::currencyCode([
            'currencies' => ['EUR' => ['name' => 'Euro']],
        ]))->toBe('EUR');

    expect(GeoCountryProfileLoader::fromRestCountriesRow(['cca2' => '']))->toBeNull()
        ->and(GeoCountryProfileLoader::fromRestCountriesRow([
            'cca2' => 'ZZ',
            'name' => ['common' => ''],
        ]))->toBeNull()
        ->and(GeoCountryProfileLoader::phoneCode(['idd' => 'invalid']))->toBeNull()
        ->and(GeoCountryProfileLoader::currencyCode(['currencies' => []]))->toBeNull();
});

test('geo country profile loader load handles blank codes and http failures', function (): void {
    $http = new class implements GeoHttpGateway
    {
        public function get(string $url): array
        {
            throw new RuntimeException('offline');
        }

        public function post(string $url, array $body): array
        {
            throw new RuntimeException('Unexpected POST '.$url);
        }
    };

    $loader = new GeoCountryProfileLoader($http);

    expect($loader->load(''))->toBeNull()
        ->and($loader->load('BE'))->toBeNull();
});

test('geo country profile loader load returns parsed profile from http gateway', function (): void {
    $http = new class implements GeoHttpGateway
    {
        public function get(string $url): array
        {
            return [
                'cca2' => 'FR',
                'cca3' => 'FRA',
                'name' => ['common' => 'France'],
                'capital' => ['Paris'],
                'population' => 68000000,
                'continents' => ['Europe'],
                'idd' => ['root' => '+3', 'suffixes' => ['3']],
                'currencies' => ['EUR' => ['name' => 'Euro']],
                'flag' => '🇫🇷',
            ];
        }

        public function post(string $url, array $body): array
        {
            throw new RuntimeException('Unexpected POST '.$url);
        }
    };

    $profile = (new GeoCountryProfileLoader($http))->load('FR');

    expect($profile['code'] ?? null)->toBe('FR')
        ->and($profile['name'] ?? null)->toBe('France');
});
