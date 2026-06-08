<?php

declare(strict_types=1);

use Velm\Modules\GeoData\GeoApiImporter;
use Velm\Modules\GeoData\GeoHttpGateway;
use Velm\Modules\ModuleInstaller;
use Velm\Modules\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('geo api importer loads countries states and cities from http gateway', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules'];
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin', 'geo_data']);

    $env = $installer->environment($roots);

    $http = new class implements GeoHttpGateway
    {
        public function get(string $url): array
        {
            if (str_contains($url, 'restcountries.com')) {
                return [
                    [
                        'cca2' => 'BE',
                        'cca3' => 'BEL',
                        'name' => ['common' => 'Belgium'],
                        'capital' => ['Brussels'],
                        'population' => 11825551,
                        'continents' => ['Europe'],
                        'idd' => ['root' => '+3', 'suffixes' => ['2']],
                        'currencies' => ['EUR' => ['name' => 'Euro', 'symbol' => '€']],
                        'flag' => '🇧🇪',
                    ],
                    [
                        'cca2' => 'FR',
                        'cca3' => 'FRA',
                        'name' => ['common' => 'France'],
                        'capital' => ['Paris'],
                        'population' => 68000000,
                        'continents' => ['Europe'],
                        'idd' => ['root' => '+3', 'suffixes' => ['3']],
                        'currencies' => ['EUR' => ['name' => 'Euro', 'symbol' => '€']],
                        'flag' => '🇫🇷',
                    ],
                ];
            }

            if (str_contains($url, 'countries/states')) {
                return [
                    'error' => false,
                    'data' => [
                        [
                            'name' => 'Belgium',
                            'iso2' => 'BE',
                            'states' => [
                                ['name' => 'Flanders', 'state_code' => 'VLG'],
                            ],
                        ],
                        [
                            'name' => 'France',
                            'iso2' => 'FR',
                            'states' => [
                                ['name' => 'Île-de-France', 'state_code' => 'IDF'],
                            ],
                        ],
                    ],
                ];
            }

            throw new RuntimeException('Unexpected GET '.$url);
        }

        public function post(string $url, array $body): array
        {
            if (! str_contains($url, 'state/cities')) {
                throw new RuntimeException('Unexpected POST '.$url);
            }

            $country = (string) ($body['country'] ?? '');
            $state = (string) ($body['state'] ?? '');

            if ($country === 'Belgium' && $state === 'Flanders') {
                return [
                    'error' => false,
                    'data' => ['Antwerp', 'Brussels', 'Ghent'],
                ];
            }

            if ($country === 'France' && $state === 'Île-de-France') {
                return [
                    'error' => false,
                    'data' => ['Paris', 'Versailles'],
                ];
            }

            return ['error' => true, 'data' => []];
        }
    };

    $counts = (new GeoApiImporter($http))->import($env);

    expect($counts)->toBe(['countries' => 2, 'states' => 2, 'cities' => 5])
        ->and($env->model('res.country')->search()->count())->toBe(2)
        ->and($env->model('res.country.state')->search()->count())->toBe(2)
        ->and($env->model('res.city')->search()->count())->toBe(5);

    $brussels = $env->model('res.city')->search([['name', '=', 'Brussels']], limit: 1)->read(['is_capital'])[0] ?? [];

    expect($brussels['is_capital'] ?? false)->toBeTrue();
});

test('geo api importer skips invalid rows and handles city api errors', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules'];
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin', 'geo_data']);

    $env = $installer->environment($roots);

    $http = new class implements GeoHttpGateway
    {
        public function get(string $url): array
        {
            if (str_contains($url, 'restcountries.com')) {
                return [
                    'not-array',
                    ['cca2' => '', 'name' => ['common' => 'Ignored']],
                    [
                        'cca2' => 'BE',
                        'cca3' => 'BEL',
                        'name' => ['common' => 'Belgium'],
                        'capital' => ['Brussels'],
                        'population' => 1,
                        'continents' => ['Europe'],
                        'idd' => ['root' => '+3', 'suffixes' => ['2']],
                        'currencies' => ['EUR' => ['name' => 'Euro']],
                    ],
                ];
            }

            return [
                'error' => false,
                'data' => [
                    [
                        'name' => 'Belgium',
                        'iso2' => 'BE',
                        'states' => [
                            'invalid',
                            ['name' => '', 'state_code' => ''],
                            ['name' => 'Flanders', 'state_code' => 'VLG'],
                        ],
                    ],
                    'invalid-country',
                ],
            ];
        }

        public function post(string $url, array $body): array
        {
            return ['error' => true, 'data' => []];
        }
    };

    $counts = (new GeoApiImporter($http))->import($env);

    expect($counts)->toBe(['countries' => 1, 'states' => 1, 'cities' => 0]);
});

test('geo api importer updates existing rows and imports capital cities', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules'];
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin', 'geo_data']);

    $env = $installer->environment($roots);

    $http = new class implements GeoHttpGateway
    {
        public int $pass = 0;

        public function get(string $url): array
        {
            if (str_contains($url, 'restcountries.com')) {
                return [
                    [
                        'cca2' => 'BE',
                        'cca3' => 'BEL',
                        'name' => ['common' => 'Belgium'],
                        'capital' => ['Brussels'],
                        'population' => 1,
                        'continents' => ['Europe'],
                        'idd' => ['root' => '+3', 'suffixes' => ['2']],
                        'currencies' => ['EUR' => ['name' => 'Euro']],
                    ],
                ];
            }

            return [
                'error' => false,
                'data' => [
                    [
                        'name' => 'Belgium',
                        'iso2' => 'BE',
                        'states' => [
                            ['name' => 'Flanders'],
                        ],
                    ],
                ],
            ];
        }

        public function post(string $url, array $body): array
        {
            return [
                'error' => false,
                'data' => ['Brussels', '', 123, 'Ghent'],
            ];
        }
    };

    $importer = new GeoApiImporter($http);
    $first = $importer->import($env);
    $second = $importer->import($env);

    expect($first)->toBe(['countries' => 1, 'states' => 1, 'cities' => 2])
        ->and($second)->toBe(['countries' => 1, 'states' => 1, 'cities' => 2])
        ->and($env->model('res.city')->search([['name', '=', 'Brussels'], ['is_capital', '=', true]])->count())->toBe(1);
});

test('geo api importer throws when geo models are not installed', function (): void {
    $env = \Velm\Registry::using(function (\Velm\Registry $registry) {
        $connection = \Velm\Database\PdoConnection::sqliteMemory();
        (new \Velm\Schema\SchemaBuilder($connection))->syncRegistry($registry);

        return new \Velm\Environment($connection, $registry, uid: 1);
    });

    expect(fn () => (new GeoApiImporter)->import($env))
        ->toThrow(RuntimeException::class, 'Geo data models are not installed.');
});
