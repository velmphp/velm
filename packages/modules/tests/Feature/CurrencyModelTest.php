<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Velm\Modules\Base\CurrencyApiImporter;
use Velm\Modules\Base\CurrencyImportService;
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

test('base install creates only the default currency active', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules'];
    $installer = new ModuleInstaller;

    $installer->installBootstrap($roots, ['base']);

    expect(Schema::hasTable('res_currency'))->toBeTrue();

    $env = $installer->environment($roots);

    expect($env->model('res.currency')->search()->count())->toBe(1)
        ->and($env->model('res.currency')->search([['active', '=', true]])->count())->toBe(1)
        ->and($env->model('res.currency')->search([['name', '=', 'EUR']])->count())->toBe(1);

    $euro = $env->model('res.currency')->search([['name', '=', 'EUR']])->read()[0];

    expect($euro['symbol'] ?? null)->toBe('€')
        ->and($euro['decimal_places'] ?? null)->toBe(2)
        ->and($euro['active'] ?? null)->toBeTrue();
});

test('default company receives eur fallback currency before geo bootstrap country exists', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules'];
    $installer = new ModuleInstaller;

    $installer->installBootstrap($roots, ['base']);

    $env = $installer->environment($roots);
    $company = $env->model('res.company')->search(limit: 1)->read(['name', 'currency_id'])[0] ?? [];
    $currency = $env->model('res.currency')->search([['name', '=', 'EUR']], limit: 1)->read(['name'])[0] ?? [];

    expect($company['name'] ?? null)->toBe('My Company')
        ->and($company['currency_id'] ?? null)->toBe($currency['id'] ?? null);
});

test('m2o search returns only active currencies', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules'];
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base']);

    $env = $installer->environment($roots);

    $activeCount = $env->model('res.currency')->search([['active', '=', true]])->count();
    $payload = (new Many2oneSearch)->search($env, 'res.currency', '', 50);

    expect($activeCount)->toBe(1)
        ->and($payload['results'])->toHaveCount(1)
        ->and($payload['results'][0]['label'] ?? null)->toBe('EUR');
});

test('currency import service loads world currencies on demand', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules'];
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base']);

    $env = $installer->environment($roots);

    expect($env->model('res.currency')->search()->count())->toBe(1);

    $service = new CurrencyImportService(new CurrencyApiImporter(fakeWorldCurrencyHttp()));
    $result = $service->import($env);

    expect($result['imported'])->toBe(13)
        ->and($env->model('res.currency')->search()->count())->toBe(13)
        ->and($env->model('res.currency')->search([['active', '=', true]])->count())->toBe(1);

    $usd = $env->model('res.currency')->search([['name', '=', 'USD']], limit: 1)->read(['active'])[0] ?? [];

    expect($usd['active'] ?? null)->toBeFalse();
});

test('base install seeds configured default currency code from env', function (): void {
    config(['velm.default_currency' => 'USD']);

    $roots = [dirname(__DIR__, 2).'/modules'];
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base']);

    $env = $installer->environment($roots);

    expect($env->model('res.currency')->search()->count())->toBe(1)
        ->and($env->model('res.currency')->search([['name', '=', 'USD']])->count())->toBe(1)
        ->and($env->model('res.currency')->search([['active', '=', true]])->count())->toBe(1);
});

test('currency reference seeder import profiles upserts inactive currencies', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules'];
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base']);
    $env = $installer->environment($roots);

    $imported = CurrencyReferenceSeeder::importProfiles($env, [
        ['code' => 'USD', 'full_name' => 'US Dollar', 'symbol' => '$', 'decimal_places' => 2],
        ['code' => 'GBP', 'full_name' => 'British Pound', 'symbol' => '£', 'decimal_places' => 2],
    ]);

    expect($imported)->toBe(2)
        ->and($env->model('res.currency')->search([['name', '=', 'USD']])->read(['active'])[0]['active'])->toBeFalse();
});

test('currency reference seeder activate only enables matching code', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules'];
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base']);
    $env = $installer->environment($roots);

    CurrencyReferenceSeeder::importProfiles($env, [
        ['code' => 'USD', 'full_name' => 'US Dollar', 'symbol' => '$', 'decimal_places' => 2],
        ['code' => 'GBP', 'full_name' => 'British Pound', 'symbol' => '£', 'decimal_places' => 2],
    ]);

    CurrencyReferenceSeeder::activateOnly($env, 'GBP');

    expect($env->model('res.currency')->search([['name', '=', 'GBP'], ['active', '=', true]])->count())->toBe(1)
        ->and($env->model('res.currency')->search([['name', '=', 'USD'], ['active', '=', true]])->count())->toBe(0);
});

test('currency reference seeder import profiles updates existing rows without toggling active', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules'];
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base']);
    $env = $installer->environment($roots);

    $eurId = $env->model('res.currency')->search([['name', '=', 'EUR']], limit: 1)->ids()[0];
    $env->browse('res.currency', [$eurId])->write(['active' => true]);

    CurrencyReferenceSeeder::importProfiles($env, [
        ['code' => 'EUR', 'full_name' => 'Euro updated', 'symbol' => '€', 'decimal_places' => 2],
    ]);

    $row = $env->model('res.currency')->search([['id', '=', $eurId]], limit: 1)->read(['full_name', 'active'])[0];

    expect($row['full_name'])->toBe('Euro updated')
        ->and($row['active'])->toBeTrue();
});
