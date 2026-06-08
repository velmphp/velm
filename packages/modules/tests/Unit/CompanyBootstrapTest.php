<?php

declare(strict_types=1);

use Velm\Modules\Base\CompanyBootstrap;
use Velm\Modules\ModuleInstaller;
use Velm\Modules\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('configured default currency code returns null when env is unset', function (): void {
    config(['velm.default_currency' => null]);
    putenv('VELM_DEFAULT_CURRENCY');

    expect(CompanyBootstrap::configuredDefaultCurrencyCode())->toBeNull();
});

test('resolve default currency uses bootstrap country currency when env is unset', function (): void {
    config(['velm.default_currency' => null, 'velm.geo_country' => 'KE']);
    putenv('VELM_DEFAULT_CURRENCY');

    $roots = [dirname(__DIR__, 2).'/modules'];
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin', 'geo_data']);

    $env = $installer->environment($roots);
    $kesId = CompanyBootstrap::currencyIdForCode($env, 'KES');

    expect(CompanyBootstrap::resolveDefaultCurrencyId($env))->toBe($kesId);
});

test('resolve default currency prefers env override over bootstrap country', function (): void {
    config(['velm.default_currency' => 'USD', 'velm.geo_country' => 'KE']);

    $roots = [dirname(__DIR__, 2).'/modules'];
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin', 'geo_data']);

    $env = $installer->environment($roots);
    $usdId = CompanyBootstrap::currencyIdForCode($env, 'USD');

    expect(CompanyBootstrap::resolveDefaultCurrencyId($env))->toBe($usdId);
});

test('configured default currency code reads velm default currency env variable', function (): void {
    putenv('VELM_DEFAULT_CURRENCY=gbp');
    config(['velm.default_currency' => null]);

    expect(CompanyBootstrap::configuredDefaultCurrencyCode())->toBe('GBP')
        ->and(CompanyBootstrap::usesEnvDefaultCurrency())->toBeTrue();

    putenv('VELM_DEFAULT_CURRENCY');
});

test('bootstrap country and currency helpers handle missing models and blank codes', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules'];
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base']);

    $env = $installer->environment($roots);

    expect(CompanyBootstrap::bootstrapCountry($env))->toBeNull()
        ->and(CompanyBootstrap::currencyIdForCode($env, ''))->toBeNull();

    $envWithoutCurrency = \Velm\Registry::using(function (\Velm\Registry $registry) {
        $connection = \Velm\Database\PdoConnection::sqliteMemory();
        (new \Velm\Schema\SchemaBuilder($connection))->syncRegistry($registry);

        return new \Velm\Environment($connection, $registry, uid: 1);
    });

    expect(CompanyBootstrap::currencyIdForCode($envWithoutCurrency, 'EUR'))->toBeNull()
        ->and(CompanyBootstrap::resolveDefaultCurrencyId($env))->toBe(
            $env->model('res.currency')->search([['active', '=', true]], limit: 1)->ids()[0],
        );
});

test('bootstrap country returns first country row when geo data is installed', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules'];
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin', 'geo_data']);

    $env = $installer->environment($roots);

    expect(CompanyBootstrap::bootstrapCountry($env))->toHaveKeys(['id', 'code']);
});

test('resolve default currency falls back to first active currency when eur is missing', function (): void {
    config(['velm.default_currency' => null, 'velm.geo_country' => 'ZZ']);
    putenv('VELM_DEFAULT_CURRENCY');

    $roots = [dirname(__DIR__, 2).'/modules'];
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base']);

    $env = $installer->environment($roots);
    $env->model('res.currency')->search([['name', '=', 'EUR']])->unlink();
    $usdId = (int) $env->model('res.currency')->create([
        'name' => 'USD',
        'full_name' => 'US Dollar',
        'symbol' => '$',
        'decimal_places' => 2,
        'active' => true,
    ])->ids()[0];
    $env->model('res.company')->search()->write(['currency_id' => false]);

    expect(CompanyBootstrap::resolveDefaultCurrencyId($env))->toBe($usdId);
});
