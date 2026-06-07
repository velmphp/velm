<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Velm\Modules\ModuleInstaller;
use Velm\Modules\ModuleRepository;
use Velm\Modules\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('uninstall with drop-schema removes module-owned tables', function (): void {
    $roots = [
        dirname(__DIR__, 2).'/modules',
    ];
    $installer = new ModuleInstaller;

    $installer->installBootstrap($roots, ['base']);
    $installer->install('partners', $roots);

    $env = $installer->environment($roots);
    $env->model('res.partner')->create(['name' => 'Acme']);

    expect(DB::getSchemaBuilder()->hasTable('res_partner'))->toBeTrue();

    $installer->uninstall('partners', $roots, ['base', 'admin'], dropSchema: true);

    expect(DB::getSchemaBuilder()->hasTable('res_partner'))->toBeFalse()
        ->and(DB::table(ModuleRepository::TABLE)->where('name', 'partners')->exists())->toBeFalse();
});

test('uninstall without drop-schema keeps module tables', function (): void {
    $roots = [
        dirname(__DIR__, 2).'/modules',
    ];
    $installer = new ModuleInstaller;

    $installer->installBootstrap($roots, ['base']);
    $installer->install('partners', $roots);

    expect(DB::getSchemaBuilder()->hasTable('res_partner'))->toBeTrue();

    $installer->uninstall('partners', $roots);

    expect(DB::getSchemaBuilder()->hasTable('res_partner'))->toBeTrue();
});

test('uninstall removes module row and module views', function (): void {
    $roots = [
        dirname(__DIR__, 2).'/modules',
        dirname(__DIR__, 2).'/tests/fixtures',
    ];
    $installer = new ModuleInstaller;

    $installer->installBootstrap($roots, ['base']);
    $installer->install('partners', $roots);
    $installer->install('partners_ext', $roots);

    $env = $installer->environment($roots);
    $viewCount = $env->model('ir.ui.view')->search([['module', '=', 'partners_ext']])->count();

    expect($viewCount)->toBeGreaterThan(0);

    $installer->uninstall('partners_ext', $roots);

    expect(DB::table(ModuleRepository::TABLE)->where('name', 'partners_ext')->exists())->toBeFalse()
        ->and($env->model('ir.ui.view')->search([['module', '=', 'partners_ext']])->count())->toBe(0)
        ->and(DB::table(ModuleRepository::TABLE)->where('name', 'partners')->exists())->toBeTrue();
});

test('cannot uninstall protected bootstrap modules', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules'];
    $installer = new ModuleInstaller;

    $installer->installBootstrap($roots, ['base', 'admin']);

    $preview = $installer->uninstallPreview('base', $roots);

    expect($preview->canUninstall)->toBeFalse()
        ->and($preview->blockers())->not->toBeEmpty();

    $installer->uninstall('base', $roots);
})->throws(RuntimeException::class);

test('cannot uninstall module with reverse dependencies', function (): void {
    $roots = [
        dirname(__DIR__, 2).'/modules',
        dirname(__DIR__, 2).'/tests/fixtures',
    ];
    $installer = new ModuleInstaller;

    $installer->installBootstrap($roots, ['base']);
    $installer->install('partners', $roots);
    $installer->install('partners_ext', $roots);

    $preview = $installer->uninstallPreview('partners', $roots);

    expect($preview->canUninstall)->toBeFalse()
        ->and($preview->reverseDependencies)->toContain('partners_ext')
        ->and($preview->blockers())->toBe([
            'The following modules depend on it: partners_ext',
        ]);

    $installer->uninstall('partners', $roots);
})->throws(RuntimeException::class);

test('cannot uninstall module extended by another installed module', function (): void {
    $roots = [
        dirname(__DIR__, 2).'/modules',
        dirname(__DIR__, 2).'/tests/fixtures',
    ];
    $installer = new ModuleInstaller;

    $installer->installBootstrap($roots, ['base']);
    $installer->install('partners', $roots);
    $installer->install('partners_ext_independent', $roots);

    $preview = $installer->uninstallPreview('partners', $roots);

    expect($preview->canUninstall)->toBeFalse()
        ->and($preview->modelExtensions)->toContain('partners_ext_independent');
});
