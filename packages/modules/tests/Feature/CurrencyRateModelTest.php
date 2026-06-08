<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Velm\Modules\ModuleInstaller;
use Velm\Modules\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('base install creates exchange rates scoped to companies', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules'];
    $installer = new ModuleInstaller;

    $installer->installBootstrap($roots, ['base']);

    expect(Schema::hasTable('res_currency_rate'))->toBeTrue();

    $env = $installer->environment($roots);
    $company = $env->model('res.company')->search(limit: 1)->read(['id', 'currency_id'])[0] ?? [];
    $rates = $env->model('res.currency.rate')->search([
        ['company_id', '=', $company['id'] ?? 0],
    ])->read(['currency_id', 'rate', 'name']);

    expect($rates)->not->toBeEmpty();

    $companyCurrencyRate = null;

    foreach ($rates as $rate) {
        if (($rate['currency_id'] ?? null) === ($company['currency_id'] ?? null)) {
            $companyCurrencyRate = $rate['rate'] ?? null;
            break;
        }
    }

    expect($companyCurrencyRate)->toBe(1.0);
});
