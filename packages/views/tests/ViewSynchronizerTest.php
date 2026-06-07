<?php

declare(strict_types=1);

use Velm\Modules\ModuleInstaller;
use Velm\Modules\ModuleSpec;
use Velm\Views\Sync\UiSyncDiff;
use Velm\Views\Sync\ViewSynchronizer;
use Velm\Views\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('ui sync diff summary lists each change bucket', function (): void {
    $diff = new UiSyncDiff;
    $diff->newViews = ['partner.form'];
    $diff->changedMenus = ['root'];
    $diff->removedMenus = ['legacy'];

    expect($diff->summary())->toBe('1 new view(s), 1 changed menu(s), 1 removed menu(s)');
});

test('ui sync diff summary falls back when no buckets are populated', function (): void {
    $diff = new UiSyncDiff;
    $diff->changedViews = ['partner.list'];

    expect($diff->summary())->toBe('1 changed view(s)');
});

test('view synchronizer updates existing base views and prunes stale rows', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules/modules'];
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'partners']);
    $env = $installer->environment($roots);
    $spec = $installer->discover($roots)['partners'];

    $env->model('ir.ui.view')->create([
        'module' => 'partners',
        'name' => 'stale.view',
        'model' => 'res.partner',
        'view_type' => 'list',
        'arch' => json_encode(['fields' => [['name' => 'name']]], JSON_THROW_ON_ERROR),
        'priority' => 16,
        'inherit_id' => null,
        'operations' => null,
    ]);

    (new ViewSynchronizer)->sync($spec, $env);

    expect($env->model('ir.ui.view')->search([
        ['module', '=', 'partners'],
        ['name', '=', 'partner.list'],
    ])->count())->toBe(1)
        ->and($env->model('ir.ui.view')->search([
            ['module', '=', 'partners'],
            ['name', '=', 'stale.view'],
        ])->count())->toBe(0);
});

test('view synchronizer purgeModule removes module views', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules/modules'];
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'partners']);
    $env = $installer->environment($roots);

    expect($env->model('ir.ui.view')->search([['module', '=', 'partners']])->count())->toBeGreaterThan(0);

    (new ViewSynchronizer)->purgeModule('partners', $env);

    expect($env->model('ir.ui.view')->search([['module', '=', 'partners']])->count())->toBe(0);
});

test('view synchronizer rejects malformed base view declarations', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules/modules'];
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base']);
    $env = $installer->environment($roots);
    $spec = new ModuleSpec('test_ui', [1, 0, 0], [], $roots[0]);

    $sync = new ViewSynchronizer;
    $method = new ReflectionMethod(ViewSynchronizer::class, 'syncBaseViews');
    $method->setAccessible(true);

    expect(fn () => $method->invoke($sync, $spec, $env, [['name' => 'broken.view']]))
        ->toThrow(InvalidArgumentException::class, 'missing keys');
});

test('view synchronizer rejects invalid arch payloads', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules/modules'];
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base']);
    $env = $installer->environment($roots);
    $spec = new ModuleSpec('test_ui', [1, 0, 0], [], $roots[0]);

    $sync = new ViewSynchronizer;
    $method = new ReflectionMethod(ViewSynchronizer::class, 'syncBaseViews');
    $method->setAccessible(true);

    expect(fn () => $method->invoke($sync, $spec, $env, [[
        'name' => 'broken.view',
        'model' => 'res.partner',
        'view_type' => 'list',
        'arch' => 'not-json',
    ]]))->toThrow(JsonException::class);
});

test('view synchronizer rejects inherit views with missing parent', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules/modules'];
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base']);
    $env = $installer->environment($roots);
    $spec = new ModuleSpec('test_ui', [1, 0, 0], [], $roots[0]);

    $sync = new ViewSynchronizer;
    $method = new ReflectionMethod(ViewSynchronizer::class, 'syncViewInherits');
    $method->setAccessible(true);

    expect(fn () => $method->invoke($sync, $spec, $env, [[
        'name' => 'missing.parent.ext',
        'inherit' => 'missing.parent',
        'operations' => [],
    ]]))->toThrow(RuntimeException::class, 'parent view missing.parent not found');
});

test('view synchronizer syncs inherit views as json string operations', function (): void {
    $roots = [dirname(__DIR__, 2).'/modules/modules'];
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'partners']);
    $env = $installer->environment($roots);
    $spec = new ModuleSpec('partners', [1, 0, 0], [], $roots[0].'/partners');

    $sync = new ViewSynchronizer;
    $method = new ReflectionMethod(ViewSynchronizer::class, 'syncViewInherits');
    $method->setAccessible(true);
    $method->invoke($sync, $spec, $env, [[
        'name' => 'partner.list.ext',
        'inherit' => 'partners.partner.list',
        'operations' => json_encode([
            ['op' => 'update', 'target' => ['fields', 'name'], 'value' => ['label' => 'Partner']],
        ], JSON_THROW_ON_ERROR),
    ]]);

    $row = $env->model('ir.ui.view')->search([
        ['module', '=', 'partners'],
        ['name', '=', 'partner.list.ext'],
    ])->read()[0];

    expect($row['inherit_id'])->not->toBeNull()
        ->and(json_decode((string) $row['operations'], true, flags: JSON_THROW_ON_ERROR))
        ->toHaveCount(1);
});

test('view synchronizer parse inherit ref rejects malformed refs', function (): void {
    $method = new ReflectionMethod(ViewSynchronizer::class, 'parseInheritRef');
    $method->setAccessible(true);

    expect(fn () => $method->invoke(new ViewSynchronizer, 'missingmodule'))
        ->toThrow(InvalidArgumentException::class, 'module.name');
});
