<?php

declare(strict_types=1);

use Velm\Modules\ModuleDependencyViewExtensionSorter;
use Velm\Modules\ModuleInstaller;
use Velm\Modules\Tests\Support\ModuleRoots;
use Velm\Modules\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('view extension sorter returns empty for empty input', function (): void {
    expect((new ModuleDependencyViewExtensionSorter)->sort([]))->toBe([]);
});

test('view extension sorter breaks ties by priority then id when module ranks are unknown', function (): void {
    config(['velm.addon_paths' => []]);

    $sorted = (new ModuleDependencyViewExtensionSorter)->sort([
        ['module' => 'z_mod', 'priority' => 20, 'id' => 5],
        ['module' => 'a_mod', 'priority' => 10, 'id' => 9],
        ['module' => 'b_mod', 'priority' => 10, 'id' => 3],
    ]);

    expect(array_column($sorted, 'id'))->toBe([3, 9, 5]);
});

test('view extension sorter orders extensions by installed module dependency rank', function (): void {
    $roots = [
        dirname(__DIR__, 2).'/modules',
        dirname(__DIR__, 2).'/tests/fixtures',
    ];
    config(['velm.addon_paths' => $roots]);

    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base']);
    $installer->install('partners', $roots);
    $installer->install('partners_ext', $roots);

    $sorted = (new ModuleDependencyViewExtensionSorter)->sort([
        ['module' => 'partners_ext', 'priority' => 16, 'id' => 2],
        ['module' => 'partners', 'priority' => 16, 'id' => 1],
    ]);

    expect(array_column($sorted, 'module'))->toBe(['partners', 'partners_ext']);
});
