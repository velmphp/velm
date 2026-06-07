<?php

declare(strict_types=1);

use Velm\Modules\ModuleInstaller;
use Velm\Modules\Tests\Support\ModuleRoots;
use Velm\Modules\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('module installer catalog and schema status report installed modules', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('partners', $roots);

    $catalog = $installer->catalog($roots);
    $status = $installer->schemaStatus($roots);

    expect(collect($catalog)->pluck('name')->all())->toContain('partners')
        ->and($status)->not->toBeEmpty()
        ->and($installer->hasPendingSchemaDiff('partners', $roots))->toBeFalse();
});

test('module installer uninstall preview lists dependents', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('partners', $roots);

    $preview = $installer->uninstallPreview('partners', $roots);

    expect($preview->module)->toBe('partners')
        ->and($preview->canUninstall)->toBeTrue()
        ->and($preview->blockers())->toBe([]);
});

test('module installer reconcile and migrate partners module', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('partners', $roots);

    $installer->reconcile('partners', $roots);
    $installer->migrate('partners', $roots);

    expect($installer->diff('partners', $roots)->isEmpty())->toBeTrue();
});

test('module installer seed and ui sync diff run for partners', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('partners', $roots);

    $installer->seed($roots, 'partners');

    $uiDiff = $installer->uiSyncDiff('partners', $roots);

    expect($installer->hasPendingUiSync('partners', $roots))->toBe($uiDiff->hasChanges())
        ->and($installer->canAlterColumnNullability())->toBeFalse();
});
