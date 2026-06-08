<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Velm\Modules\ModuleInstaller;
use Velm\Modules\ModuleRepository;
use Velm\Modules\ModulesServiceProvider;
use Velm\Modules\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('installs bootstrap modules on a fresh database', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules'];
    $installer = new ModuleInstaller;

    $installer->installBootstrap($roots, ['base', 'admin', 'geo_data', 'file_manager']);

    expect(DB::table(ModuleRepository::TABLE)->pluck('name')->sort()->values()->all())
        ->toBe(['admin', 'base', 'file_manager', 'geo_data']);
});

test('install pulls in undeclared transitive dependencies', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules'];
    $installer = new ModuleInstaller;

    $installer->install('admin', $roots);

    expect(DB::table(ModuleRepository::TABLE)->pluck('name')->sort()->values()->all())
        ->toBe(['admin', 'base']);
});
