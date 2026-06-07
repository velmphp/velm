<?php

declare(strict_types=1);

use Velm\Modules\ModuleInstaller;
use Velm\Modules\Workflow\WorkflowEngine;
use Velm\Modules\Workflow\WorkflowHistory;
use Velm\Modules\Workflow\WorkflowService;
use Velm\Modules\Tests\Support\ModuleRoots;
use Velm\Modules\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('workflow history timeline includes pending approval events', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('partners', $roots);
    $installer->install('workflow', $roots);

    $env = $installer->environment($roots);
    $partner = $env->model('res.partner')->create(['name' => 'History Partner']);
    $partnerId = $partner->ids()[0];

    WorkflowService::startForRecord($env, 'res.partner', $partnerId);
    $inst = WorkflowEngine::instanceForRecord($env, 'res.partner', $partnerId);
    expect($inst)->not->toBeNull();

    WorkflowEngine::applyTransition($env, $inst, 'submit', [
        'submission_note' => 'Please approve',
    ]);

    $timeline = WorkflowHistory::recordTimeline(
        $env,
        'res.partner',
        $partnerId,
        (int) $inst['id'],
        (string) ($inst['definition_id'] ?? ''),
    );

    expect($timeline)->not->toBeEmpty()
        ->and(collect($timeline)->pluck('kind'))->toContain('pending');
});
