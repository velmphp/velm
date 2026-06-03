<?php

declare(strict_types=1);

use Velm\Modules\AppsCatalog;
use Velm\Modules\ModuleInstaller;
use Velm\Modules\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('file_manager is installed immediately after install', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules'];
    $installer = new ModuleInstaller;
    $catalog = new AppsCatalog;

    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('file_manager', $roots);

    $entry = $catalog->entry($roots, 'file_manager');

    expect($entry)->not->toBeNull()
        ->and($entry['state'])->toBe('installed')
        ->and($entry['has_ui_sync'])->toBeFalse()
        ->and($entry['has_schema_diff'])->toBeFalse();
});

test('file_manager is installed after sync removes stale views from disk', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules'];
    $installer = new ModuleInstaller;
    $catalog = new AppsCatalog;

    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('file_manager', $roots);

    $env = $installer->environment($roots);
    $env->model('ir.ui.view')->create([
        'module' => 'file_manager',
        'name' => 'orphan.view',
        'model' => 'ir.attachment',
        'view_type' => 'list',
        'arch' => json_encode(['view_type' => 'list', 'model' => 'ir.attachment', 'columns' => ['name']], JSON_THROW_ON_ERROR),
        'priority' => 16,
    ]);

    expect($catalog->entry($roots, 'file_manager')['state'])->toBe('needs_sync');

    $installer->sync('file_manager', $roots);

    expect($catalog->entry($roots, 'file_manager')['state'])->toBe('installed');
});

test('file_manager is installed after sync when ui differs from disk', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules'];
    $installer = new ModuleInstaller;
    $catalog = new AppsCatalog;

    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('file_manager', $roots);

    $before = $catalog->entry($roots, 'file_manager');
    expect($before)->not->toBeNull()
        ->and($before['state'])->toBe('installed');

    $env = $installer->environment($roots);
    $menu = $env->model('ir.ui.menu')->search([
        ['module', '=', 'file_manager'],
        ['name', '=', 'files.library'],
    ]);
    $menu->write(['sequence' => 99]);

    $pending = $catalog->entry($roots, 'file_manager');
    expect($pending['state'])->toBe('needs_sync')
        ->and($pending['has_ui_sync'])->toBeTrue();

    $diff = $installer->uiSyncDiff('file_manager', $roots);

    $installer->sync('file_manager', $roots);

    $after = $catalog->entry($roots, 'file_manager');
    expect($after['state'])->toBe('installed')
        ->and($installer->hasPendingUiSync('file_manager', $roots))->toBeFalse();
});
