<?php

declare(strict_types=1);

use Velm\Modules\Base\CurrencyApiImporter;
use Velm\Modules\GeoData\GeoHttpGateway;
use Velm\Modules\Tests\TestCase;

uses(TestCase::class);

test('currency api importer aggregates unique currencies from restcountries payload', function (): void {
    $http = new class implements GeoHttpGateway
    {
        public function get(string $url): array
        {
            return [
                ['currencies' => ['EUR' => ['name' => 'Euro', 'symbol' => '€']]],
                ['currencies' => ['USD' => ['name' => 'United States dollar', 'symbol' => '$']]],
                ['currencies' => [
                    'EUR' => ['name' => 'Euro', 'symbol' => '€'],
                    'XPF' => ['name' => 'CFP franc', 'symbol' => '₣'],
                ]],
            ];
        }

        public function post(string $url, array $body): array
        {
            throw new RuntimeException('Unexpected POST '.$url);
        }
    };

    $profiles = (new CurrencyApiImporter($http))->fetchProfiles();

    expect($profiles)->toHaveCount(3)
        ->and(array_column($profiles, 'code'))->toBe(['EUR', 'USD', 'XPF'])
        ->and(collect($profiles)->firstWhere('code', 'JPY'))->toBeNull()
        ->and(collect($profiles)->firstWhere('code', 'EUR')['decimal_places'] ?? null)->toBe(2);
});

test('currency api importer falls back to bundled profiles when http fails', function (): void {
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

    $profiles = (new CurrencyApiImporter($http))->fetchProfiles();

    expect($profiles)->not->toBeEmpty()
        ->and(collect($profiles)->pluck('code'))->toContain('EUR');
});

test('currency api importer falls back when payload is empty or invalid', function (): void {
    $emptyHttp = new class implements GeoHttpGateway
    {
        public function get(string $url): array
        {
            return [];
        }

        public function post(string $url, array $body): array
        {
            throw new RuntimeException('Unexpected POST '.$url);
        }
    };

    $invalidHttp = new class implements GeoHttpGateway
    {
        public function get(string $url): array
        {
            return [
                'not-a-country',
                ['currencies' => 'invalid'],
                ['currencies' => [123 => ['name' => 'Bad']]],
            ];
        }

        public function post(string $url, array $body): array
        {
            throw new RuntimeException('Unexpected POST '.$url);
        }
    };

    expect((new CurrencyApiImporter($emptyHttp))->fetchProfiles())->not->toBeEmpty()
        ->and((new CurrencyApiImporter($invalidHttp))->fetchProfiles())->not->toBeEmpty();
});
