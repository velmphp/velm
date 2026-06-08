<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Velm\Environment;
use Velm\Modules\ModuleInstaller;
use Velm\Modules\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('installing geo_data creates geography tables and extends countries', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules'];
    $installer = new ModuleInstaller;

    $installer->installBootstrap($roots, ['base', 'admin', 'geo_data']);

    expect(Schema::hasTable('res_continent'))->toBeTrue()
        ->and(Schema::hasTable('res_country_state'))->toBeTrue()
        ->and(Schema::hasTable('res_city'))->toBeTrue();

    $env = $installer->environment($roots);

    expect($env->registry->has('res.continent'))->toBeTrue()
        ->and($env->registry->fieldSet('res.country'))->toHaveKeys([
            'continent_id',
            'iso3',
            'flag_emoji',
        ]);
});

test('geo reference seeder loads only the detected country and its continent', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules'];
    $installer = new ModuleInstaller;

    $installer->installBootstrap($roots, ['base', 'admin', 'geo_data']);

    $env = $installer->environment($roots);

    expect($env->model('res.continent')->search()->count())->toBe(1)
        ->and($env->model('res.country')->search()->count())->toBe(1)
        ->and($env->model('res.country')->search([['code', '=', 'BE']])->count())->toBe(1)
        ->and($env->model('res.country')->search([['code', '=', 'FR']])->count())->toBe(0);

    $belgium = $env->model('res.country')->search([['code', '=', 'BE']])->read()[0];

    expect($belgium['iso3'] ?? null)->toBe('BEL')
        ->and($belgium['flag_emoji'] ?? null)->toBe('🇧🇪')
        ->and($belgium['continent_id'] ?? null)->not->toBeNull()
        ->and($belgium['currency_id'] ?? null)->not->toBeNull();

    $euro = $env->model('res.currency')->search([['name', '=', 'EUR']], limit: 1)->ids()[0] ?? null;

    expect($belgium['currency_id'] ?? null)->toBe($euro);

    $company = $env->model('res.company')->search(limit: 1)->read(['country_id', 'currency_id'])[0] ?? [];

    expect($company['country_id'] ?? null)->toBe($belgium['id'] ?? null)
        ->and($company['currency_id'] ?? null)->toBe($euro);
});

test('bootstrap company uses country currency when default currency env is unset', function (): void {
    config(['velm.default_currency' => null, 'velm.geo_country' => 'KE']);
    putenv('VELM_DEFAULT_CURRENCY');

    $roots = [dirname(__DIR__, 2).'/modules'];
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin', 'geo_data']);

    $env = $installer->environment($roots);

    $kenya = $env->model('res.country')->search([['code', '=', 'KE']], limit: 1)->read(['id', 'currency_id'])[0] ?? [];
    $kes = $env->model('res.currency')->search([['name', '=', 'KES']], limit: 1)->ids()[0] ?? null;
    $company = $env->model('res.company')->search(limit: 1)->read(['country_id', 'currency_id'])[0] ?? [];

    expect($kenya['currency_id'] ?? null)->toBe($kes)
        ->and($company['country_id'] ?? null)->toBe($kenya['id'] ?? null)
        ->and($company['currency_id'] ?? null)->toBe($kes)
        ->and($env->model('res.currency')->search([['active', '=', true]])->count())->toBe(1)
        ->and($env->model('res.currency')->search([['name', '=', 'KES'], ['active', '=', true]])->count())->toBe(1);
});

test('partners are company scoped via company_id many2one', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules'];
    $installer = new ModuleInstaller;

    $installer->installBootstrap($roots, ['base']);
    $installer->install('partners', $roots);

    /** @var Environment $env */
    $env = $installer->environment($roots);

    $fields = $env->registry->fieldSet('res.partner');

    expect($fields['company_id']->comodel)->toBe('res.company')
        ->and($fields['country_id']->comodel)->toBe('res.country');

    $companyA = $env->model('res.company')->create(['name' => 'Scope A']);
    $companyB = $env->model('res.company')->create(['name' => 'Scope B']);

    $env->model('res.partner')->create(['name' => 'Scoped A', 'company_id' => $companyA->ids()[0]]);
    $env->model('res.partner')->create(['name' => 'Scoped B', 'company_id' => $companyB->ids()[0]]);

    $scoped = $env->withContext(['company_id' => $companyA->ids()[0]]);
    $names = array_column($scoped->model('res.partner')->search()->read(['name']), 'name');

    expect($names)->toBe(['Scoped A']);
});

test('country partner_ids one2many reads inverse country_id from merged field set', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules'];
    $installer = new ModuleInstaller;

    $installer->installBootstrap($roots, ['base', 'admin', 'geo_data']);
    $installer->install('partners', $roots);

    /** @var Environment $env */
    $env = $installer->environment($roots);

    $belgium = $env->model('res.country')->search([['code', '=', 'BE']])->ids()[0];
    $partner = $env->model('res.partner')->create([
        'name' => 'Belgian Partner',
        'country_id' => $belgium,
    ]);

    $row = $env->model('res.country')->search([['id', '=', $belgium]])->read(['partner_ids'])[0];

    expect($row['partner_ids'])->toContain($partner->ids()[0]);
});
