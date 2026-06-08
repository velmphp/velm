<?php

declare(strict_types=1);

use Velm\Modules\GeoData\GeoCountryDetector;
use Velm\Modules\GeoData\GeoCountryProfileLoader;
use Velm\Modules\GeoData\GeoHttpGateway;
use Velm\Modules\GeoData\GeoReferenceSeedAction;
use Velm\Modules\ModuleInstaller;
use Velm\Modules\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('geo reference seed action installs only the detected country profile', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules'];
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin', 'geo_data']);

    $env = $installer->environment($roots);
    $env->connection->execute('DELETE FROM res_country');
    $env->connection->execute('DELETE FROM res_continent');

    $http = new class implements GeoHttpGateway
    {
        public function get(string $url): array
        {
            if (str_contains($url, 'restcountries.com')) {
                return [
                    'cca2' => 'FR',
                    'cca3' => 'FRA',
                    'name' => ['common' => 'France'],
                    'capital' => ['Paris'],
                    'population' => 68000000,
                    'continents' => ['Europe'],
                    'idd' => ['root' => '+3', 'suffixes' => ['3']],
                    'currencies' => ['EUR' => ['name' => 'Euro', 'symbol' => '€']],
                    'flag' => '🇫🇷',
                ];
            }

            throw new RuntimeException('Unexpected GET '.$url);
        }

        public function post(string $url, array $body): array
        {
            throw new RuntimeException('Unexpected POST '.$url);
        }
    };

    (new GeoReferenceSeedAction(
        new GeoCountryDetector($http, 'FR'),
        new GeoCountryProfileLoader($http),
    ))->run($env);

    expect($env->model('res.continent')->search()->count())->toBe(1)
        ->and($env->model('res.country')->search()->count())->toBe(1)
        ->and($env->model('res.country')->search([['code', '=', 'FR']])->count())->toBe(1)
        ->and($env->model('res.country')->search([['code', '=', 'BE']])->count())->toBe(0);
});
