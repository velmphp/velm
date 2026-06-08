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
