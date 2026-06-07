<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Velm\Modules\ModuleDiscovery;
use Velm\Modules\ModuleInstaller;
use Velm\Modules\Schema\ModuleSchemaDrop;
use Velm\Modules\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('module schema drop make returns configured instance', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules'];
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base']);
    $env = $installer->environment($roots);

    expect(ModuleSchemaDrop::make($env->connection, $roots, ['base']))
        ->toBeInstanceOf(ModuleSchemaDrop::class);
});

test('module schema drop removes partner tables when module is uninstalled from disk', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules'];
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base']);
    $installer->install('partners', $roots);

    $env = $installer->environment($roots);
    $spec = (new ModuleDiscovery)->discover($roots)['partners'];

    expect(DB::getSchemaBuilder()->hasTable('res_partner'))->toBeTrue();

    (new ModuleSchemaDrop($env->connection))->dropModule($spec, $roots, ['base']);

    expect(DB::getSchemaBuilder()->hasTable('res_partner'))->toBeFalse();
});

test('module schema drop no-ops when another installed module owns the models', function (): void {
    $roots = [
        dirname(__DIR__, 2).'/modules',
        dirname(__DIR__, 2).'/tests/fixtures',
    ];
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base']);
    $installer->install('partners', $roots);
    $installer->install('partners_ext', $roots);

    $env = $installer->environment($roots);
    $spec = (new ModuleDiscovery)->discover($roots)['partners_ext'];

    (new ModuleSchemaDrop($env->connection))->dropModule($spec, $roots, ['base', 'partners', 'partners_ext']);

    expect(DB::getSchemaBuilder()->hasTable('res_partner'))->toBeTrue();
});
