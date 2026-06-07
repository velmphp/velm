<?php

declare(strict_types=1);

use Velm\Modules\ModuleInstaller;
use Velm\Modules\Partners\Seeders\PartnerDemoSeeder;
use Velm\Modules\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

/**
 * @return list<string>
 */
function partnerSeederRoots(): array
{
    return [dirname(__DIR__, 2).'/modules'];
}

function installPartnersModule(): \Velm\Environment
{
    $roots = partnerSeederRoots();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base']);
    $installer->install('partners', $roots);

    return $installer->environment($roots);
}

test('partner demo seeder creates countries and partners for analytics views', function (): void {
    $env = installPartnersModule();

    expect($env->model('res.country')->search()->count())->toBe(4)
        ->and($env->model('res.partner')->search()->count())->toBe(6);

    $active = $env->model('res.partner')->search([['active', '=', true]])->count();
    $companies = $env->model('res.partner')->search([['is_company', '=', true]])->count();

    expect($active)->toBe(4)
        ->and($companies)->toBe(4);
});

test('partner demo seeder is idempotent', function (): void {
    $env = installPartnersModule();

    PartnerDemoSeeder::run($env);
    PartnerDemoSeeder::run($env);

    expect($env->model('res.country')->search()->count())->toBe(4)
        ->and($env->model('res.partner')->search()->count())->toBe(6);
});

test('partners install runs demo seeder without velm:seed', function (): void {
    $env = installPartnersModule();

    expect($env->model('res.partner')->search()->count())->toBe(6);
});

test('partners sync re-runs demo seeder', function (): void {
    $roots = partnerSeederRoots();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base']);
    $installer->install('partners', $roots);

    $env = $installer->environment($roots);
    $env->model('res.partner')->search()->unlink();

    expect($env->model('res.partner')->search()->count())->toBe(0);

    $installer->sync('partners', $roots);

    expect($env->model('res.partner')->search()->count())->toBe(6);
});

test('partners manifest registers demo seeder', function (): void {
    $specs = (new \Velm\Modules\ModuleDiscovery)->discover(partnerSeederRoots());

    expect($specs['partners']->seeders)->toBe([
        PartnerDemoSeeder::class,
    ]);
});
