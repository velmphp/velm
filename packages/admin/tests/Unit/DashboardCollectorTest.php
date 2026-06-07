<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Velm\Admin\Dashboard\DashboardCollector;
use Velm\Admin\Tests\TestCase;
use Velm\Framework\VelmManager;
use Velm\Modules\Dashboard\DashboardWidgetSpec;
use Velm\Modules\Manifest;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('dashboard collector skips installed modules without discovery spec', function (): void {
    DB::table('ir_module')->insert([
        'name' => 'ghost_dashboard_module',
        'version' => '1.0.0',
        'installed_at' => now(),
    ]);

    $collector = new DashboardCollector;
    $widgets = $collector->collect(config('velm.addon_paths', []));

    expect($widgets)->not->toBeEmpty();

    DB::table('ir_module')->where('name', 'ghost_dashboard_module')->delete();
});

test('dashboard collector rejects dashboard files that do not return DashboardData', function (): void {
    $tempRoot = sys_get_temp_dir().'/velm_dash_bad_'.uniqid('', true);
    $moduleDir = $tempRoot.'/dash_bad';
    mkdir($moduleDir, 0777, true);
    file_put_contents($moduleDir.'/__velm__.php', <<<'PHP'
<?php
use Velm\Modules\Manifest;
return Manifest::make('dash_bad')->version(1, 0, 0)->depends('base');
PHP);
    file_put_contents($moduleDir.'/dashboard.php', "<?php\nreturn ['invalid' => true];\n");

    config(['velm.addon_paths' => array_merge([$tempRoot], config('velm.addon_paths', []))]);
    app(VelmManager::class)->install('dash_bad');

    $collector = new DashboardCollector;

    try {
        expect(fn () => $collector->collect([$tempRoot]))
            ->toThrow(RuntimeException::class, 'must return a DashboardData instance');
    } finally {
        @unlink($moduleDir.'/dashboard.php');
        @unlink($moduleDir.'/__velm__.php');
        @rmdir($moduleDir);
        @rmdir($tempRoot);
    }
});

test('dashboard collector sorts widgets by sequence module and id', function (): void {
    $tempRoot = sys_get_temp_dir().'/velm_dash_sort_'.uniqid('', true);
    $moduleDir = $tempRoot.'/dash_sort';
    mkdir($moduleDir, 0777, true);
    file_put_contents($moduleDir.'/__velm__.php', <<<'PHP'
<?php
use Velm\Modules\Manifest;
return Manifest::make('dash_sort')->version(1, 0, 0)->depends('base');
PHP);
    file_put_contents($moduleDir.'/dashboard.php', <<<'PHP'
<?php
use Velm\Modules\Dashboard\DashboardData;
return DashboardData::make('dash_sort')
    ->widget(id: 'z_widget', title: 'Z', model: 'res.partner', view: 'v', resolver: 'Velm\\Modules\\Partners\\Dashboard\\PartnersSummaryWidget::resolve', sequence: 5)
    ->widget(id: 'a_widget', title: 'A', model: 'res.partner', view: 'v', resolver: 'Velm\\Modules\\Partners\\Dashboard\\PartnersSummaryWidget::resolve', sequence: 5);
PHP);

    config(['velm.addon_paths' => array_merge([$tempRoot], config('velm.addon_paths', []))]);
    app(VelmManager::class)->install('dash_sort');

    $collector = new DashboardCollector;
    $ids = array_map(
        static fn (DashboardWidgetSpec $widget): string => $widget->id,
        array_values(array_filter(
            $collector->collect([$tempRoot]),
            static fn (DashboardWidgetSpec $widget): bool => $widget->module === 'dash_sort',
        )),
    );

    try {
        expect($ids)->toBe(['a_widget', 'z_widget']);
    } finally {
        @unlink($moduleDir.'/dashboard.php');
        @unlink($moduleDir.'/__velm__.php');
        @rmdir($moduleDir);
        @rmdir($tempRoot);
    }
});
