<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Velm\Modules\Base\CurrencyApiImporter;
use Velm\Modules\Base\Seeders\CurrencyReferenceSeeder;
use Velm\Modules\GeoData\GeoHttpGateway;
use Velm\Modules\ModuleInstaller;
use Velm\Modules\Tests\TestCase;
use Velm\Web\Api\Many2oneSearch;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

function fakeWorldCurrencyHttp(): GeoHttpGateway
{
    return new class implements GeoHttpGateway
    {
        public function get(string $url): array
        {
            if (str_contains($url, 'restcountries.com')) {
                return [
                    ['currencies' => ['EUR' => ['name' => 'Euro', 'symbol' => '€']]],
                    ['currencies' => ['USD' => ['name' => 'United States dollar', 'symbol' => '$']]],
                    ['currencies' => ['GBP' => ['name' => 'British pound', 'symbol' => '£']]],
                    ['currencies' => ['KES' => ['name' => 'Kenyan shilling', 'symbol' => 'KSh']]],
                    ['currencies' => ['JPY' => ['name' => 'Japanese yen', 'symbol' => '¥']]],
                    ['currencies' => ['CHF' => ['name' => 'Swiss franc', 'symbol' => 'Fr.']]],
                    ['currencies' => ['CAD' => ['name' => 'Canadian dollar', 'symbol' => '$']]],
                    ['currencies' => ['AUD' => ['name' => 'Australian dollar', 'symbol' => '$']]],
                    ['currencies' => ['CNY' => ['name' => 'Chinese yuan', 'symbol' => '¥']]],
                    ['currencies' => ['INR' => ['name' => 'Indian rupee', 'symbol' => '₹']]],
                    ['currencies' => ['BRL' => ['name' => 'Brazilian real', 'symbol' => 'R$']]],
                    ['currencies' => ['NGN' => ['name' => 'Nigerian naira', 'symbol' => '₦']]],
                    ['currencies' => ['ZAR' => ['name' => 'South African rand', 'symbol' => 'R']]],
                ];
            }

            throw new RuntimeException('Unexpected GET '.$url);
        }

        public function post(string $url, array $body): array
        {
            throw new RuntimeException('Unexpected POST '.$url);
        }
    };
}

test('base install creates res.currency and seeds world currencies inactive except default', function (): void {
    CurrencyReferenceSeeder::usingImporter(new CurrencyApiImporter(fakeWorldCurrencyHttp()));

    $roots = [dirname(__DIR__, 2).'/modules'];
    $installer = new ModuleInstaller;

    $installer->installBootstrap($roots, ['base']);

    expect(Schema::hasTable('res_currency'))->toBeTrue();

    $env = $installer->environment($roots);

    expect($env->model('res.currency')->search()->count())->toBe(13)
        ->and($env->model('res.currency')->search([['active', '=', true]])->count())->toBe(1)
        ->and($env->model('res.currency')->search([['name', '=', 'EUR']])->count())->toBe(1);

    $euro = $env->model('res.currency')->search([['name', '=', 'EUR']])->read()[0];

    expect($euro['symbol'] ?? null)->toBe('€')
        ->and($euro['decimal_places'] ?? null)->toBe(2)
        ->and($euro['active'] ?? null)->toBeTrue();

    $usd = $env->model('res.currency')->search([['name', '=', 'USD']], limit: 1)->read(['active'])[0] ?? [];

    expect($usd['active'] ?? null)->toBeFalse();

    CurrencyReferenceSeeder::usingImporter(null);
});

test('default company receives eur fallback currency before geo bootstrap country exists', function (): void {
    CurrencyReferenceSeeder::usingImporter(new CurrencyApiImporter(fakeWorldCurrencyHttp()));

    $roots = [dirname(__DIR__, 2).'/modules'];
    $installer = new ModuleInstaller;

    $installer->installBootstrap($roots, ['base']);

    $env = $installer->environment($roots);
    $company = $env->model('res.company')->search(limit: 1)->read(['name', 'currency_id'])[0] ?? [];
    $currency = $env->model('res.currency')->search([['name', '=', 'EUR']], limit: 1)->read(['name'])[0] ?? [];

    expect($company['name'] ?? null)->toBe('My Company')
        ->and($company['currency_id'] ?? null)->toBe($currency['id'] ?? null);

    CurrencyReferenceSeeder::usingImporter(null);
});

test('m2o search returns only active currencies', function (): void {
    CurrencyReferenceSeeder::usingImporter(new CurrencyApiImporter(fakeWorldCurrencyHttp()));

    $roots = [dirname(__DIR__, 2).'/modules'];
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base']);

    $env = $installer->environment($roots);

    $activeCount = $env->model('res.currency')->search([['active', '=', true]])->count();
    $payload = (new Many2oneSearch)->search($env, 'res.currency', '', 50);

    expect($activeCount)->toBe(1)
        ->and($payload['results'])->toHaveCount(1)
        ->and($payload['results'][0]['label'] ?? null)->toBe('EUR');

    CurrencyReferenceSeeder::usingImporter(null);
});
