<?php

declare(strict_types=1);

use Velm\Modules\ModuleInstaller;
use Velm\Modules\Workflow\WorkflowEngine;
use Velm\Modules\Workflow\WorkflowRuntime;
use Velm\Modules\Tests\Support\ModuleRoots;
use Velm\Modules\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('workflow runtime auto starts partner records when definition has auto_start', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('partners', $roots);
    $installer->install('workflow', $roots);

    $env = $installer->environment($roots);
    $partnerId = $env->model('res.partner')->create(['name' => 'Auto Start Partner'])->ids()[0];

    expect(WorkflowEngine::instanceForRecord($env, 'res.partner', $partnerId))->toBeNull();

    WorkflowRuntime::maybeAutoStart($env, 'res.partner', $partnerId);

    expect(WorkflowEngine::instanceForRecord($env, 'res.partner', $partnerId))->not->toBeNull();
});

test('workflow runtime backfill auto start creates missing instances', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('workflow', $roots);
    $installer->install('change_management', $roots);

    $env = $installer->environment($roots);
    $env->model('it.change')->create([
        'name' => 'Backfill change',
        'change_type' => 'standard',
        'priority' => '2',
        'risk_level' => 'low',
    ]);

    $count = WorkflowRuntime::backfillAutoStart($env, 'it.change');

    expect($count)->toBeGreaterThanOrEqual(1);
});
