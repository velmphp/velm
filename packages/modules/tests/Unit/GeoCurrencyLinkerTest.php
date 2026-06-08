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
