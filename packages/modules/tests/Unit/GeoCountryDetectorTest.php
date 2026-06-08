<?php

declare(strict_types=1);

use Velm\Modules\GeoData\GeoCountryDetector;
use Velm\Modules\GeoData\GeoHttpGateway;
use Velm\Modules\Tests\TestCase;

uses(TestCase::class);

test('geo country detector uses configured override before network lookup', function (): void {
    $http = new class implements GeoHttpGateway
    {
        public function get(string $url): array
        {
            throw new RuntimeException('Network lookup should not run when override is set.');
        }

        public function post(string $url, array $body): array
        {
            throw new RuntimeException('Unexpected POST '.$url);
        }
    };

    expect((new GeoCountryDetector($http, 'FR'))->detect())->toBe('FR');
});

test('geo country detector resolves country code from ip geolocation api', function (): void {
    config(['velm.geo_country' => null]);
    putenv('VELM_GEO_COUNTRY');

    $http = new class implements GeoHttpGateway
    {
        public function get(string $url): array
        {
            return ['country_code' => 'nl'];
        }

        public function post(string $url, array $body): array
        {
            throw new RuntimeException('Unexpected POST '.$url);
        }
    };

    expect((new GeoCountryDetector($http))->detect())->toBe('NL');
});
