<?php

declare(strict_types=1);

use Velm\Modules\AppsCatalog;
use Velm\Modules\ModuleInstaller;
use Velm\Modules\Tests\TestCase;

uses(TestCase::class);

test('apps catalog includes manifest metadata and dependency state', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules'];
    $catalog = (new AppsCatalog)->entries($roots);

    expect($catalog)->not->toBeEmpty();

    $base = collect($catalog)->firstWhere('name', 'base');

    expect($base)->not->toBeNull()
        ->and($base)->toHaveKeys([
            'display_name', 'summary', 'category', 'available_version',
            'state', 'depends', 'deps_missing', 'deps_unknown',
            'has_schema_diff', 'version_upgrade',
        ])
        ->and($base['state'])->toBeIn(['installed', 'to_upgrade', 'needs_sync', 'uninstalled']);
});

test('module installer catalog delegates to apps catalog', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules'];
    $row = (new ModuleInstaller)->catalog($roots)[0];

    expect($row)->toHaveKey('display_name')
        ->and($row)->not->toHaveKey('version');
});

test('apps catalog disables uninstall for configured bootstrap modules', function (): void {
    config(['velm.bootstrap_modules' => ['base', 'admin', 'geo_data', 'file_manager']]);

    $roots = [dirname(__DIR__, 2).'/modules'];
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin', 'geo_data', 'file_manager']);

    $catalog = collect((new AppsCatalog)->entries($roots));

    expect($catalog->firstWhere('name', 'geo_data')['can_uninstall'] ?? null)->toBeFalse()
        ->and($catalog->firstWhere('name', 'file_manager')['can_uninstall'] ?? null)->toBeFalse()
        ->and($catalog->firstWhere('name', 'geo_data')['uninstall_blockers'] ?? [])->toContain('geo_data is a protected system module')
        ->and($catalog->firstWhere('name', 'file_manager')['uninstall_blockers'] ?? [])->toContain('file_manager is a protected system module');
});

test('apps catalog entry lookup returns one module row', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules'];
    $catalog = new AppsCatalog;

    expect($catalog->entry($roots, 'base')['name'] ?? null)->toBe('base')
        ->and($catalog->entry($roots, 'missing-module'))->toBeNull();
});

test('apps catalog summarizes schema and drift diffs via private helpers', function (): void {
    $catalog = new AppsCatalog;
    $diff = new \Velm\Schema\SchemaDiff;
    $diff->newTables = [['demo_table', 'Demo']];
    $diff->newColumns = [['demo_table', 'demo_col', \Velm\Fields\CharField::make()]];
    $diff->alterations = [new \Velm\Schema\SchemaAlteration('demo_table', 'demo_col', 'nullable', 'allow null')];
    $diff->orphanColumns = [['demo_table', 'orphan_col']];

    $actionable = new ReflectionMethod(AppsCatalog::class, 'summarizeActionableDiff');
    $actionable->setAccessible(true);
    $drift = new ReflectionMethod(AppsCatalog::class, 'summarizeDriftDiff');
    $drift->setAccessible(true);
    $combine = new ReflectionMethod(AppsCatalog::class, 'combineSyncSummaries');
    $combine->setAccessible(true);

    expect($actionable->invoke($catalog, $diff, true))->toContain('new table')
        ->and($actionable->invoke($catalog, new \Velm\Schema\SchemaDiff, true))->toBe('Schema changes pending')
        ->and($drift->invoke($catalog, $diff, false))->toContain('orphan column')
        ->and($drift->invoke($catalog, new \Velm\Schema\SchemaDiff, false))->toBe('Schema drift (Sync cannot auto-fix)')
        ->and($combine->invoke($catalog, 'Schema', 'UI'))->toBe('Schema; UI')
        ->and($combine->invoke($catalog, '', ''))->toBe('');
});
