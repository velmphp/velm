<?php

declare(strict_types=1);

use Velm\Modules\GeoData\GeoCurrencyLinker;
use Velm\Modules\ModuleInstaller;
use Velm\Modules\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('geo currency linker maps country currency code to res.currency', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules'];
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin', 'geo_data']);

    $env = $installer->environment($roots);

    $values = GeoCurrencyLinker::apply($env, [
        'name' => 'Kenya',
        'code' => 'KE',
        '_currency_code' => 'KES',
    ]);

    expect($values)->toHaveKey('currency_id');

    $currency = $env->model('res.currency')->search([['name', '=', 'KES']], limit: 1)->read(['full_name', 'active'])[0] ?? [];

    expect(strtolower((string) ($currency['full_name'] ?? '')))->toBe('kenyan shilling')
        ->and($values['currency_id'] ?? null)->toBe($currency['id'] ?? null)
        ->and($currency['active'] ?? null)->toBeFalse();
});

test('geo currency linker skips invalid currency codes and syncs company defaults', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules'];
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin', 'geo_data']);

    $env = $installer->environment($roots);

    expect(GeoCurrencyLinker::apply($env, [
        'name' => 'No Currency',
        'code' => 'XX',
        '_currency_code' => '',
    ]))->not->toHaveKey('currency_id');

    $companyId = (int) $env->model('res.company')->search([], limit: 1)->ids()[0];
    $env->browse('res.company', [$companyId])->write(['country_id' => false, 'currency_id' => false]);

    GeoCurrencyLinker::syncCompanyDefaults($env);

    $company = $env->browse('res.company', [$companyId])->read(['country_id', 'currency_id'])[0];

    expect($company['country_id'] ?? null)->not->toBeFalse()
        ->and($company['currency_id'] ?? null)->not->toBeFalse();

    GeoCurrencyLinker::syncCompanyCurrency($env);
});

test('geo currency linker sync no-ops when company or country models are unavailable', function (): void {
    $env = \Velm\Registry::using(function (\Velm\Registry $registry) {
        $connection = \Velm\Database\PdoConnection::sqliteMemory();
        (new \Velm\Schema\SchemaBuilder($connection))->syncRegistry($registry);

        return new \Velm\Environment($connection, $registry, uid: 1);
    });

    GeoCurrencyLinker::syncCompanyDefaults($env);

    config(['velm.default_currency' => 'USD']);
    $roots = [dirname(__DIR__, 2).'/modules'];
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin', 'geo_data']);
    $geoEnv = $installer->environment($roots);
    $geoEnv->connection->execute('DELETE FROM res_country');

    GeoCurrencyLinker::syncCompanyDefaults($geoEnv);

    expect(true)->toBeTrue();
});
