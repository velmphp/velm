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

test('workflow history timeline includes approved events after act', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('partners', $roots);
    $installer->install('workflow', $roots);

    $env = $installer->environment($roots);
    $partnerId = $env->model('res.partner')->create(['name' => 'Approved History'])->ids()[0];

    WorkflowService::startForRecord($env, 'res.partner', $partnerId);
    $inst = WorkflowEngine::instanceForRecord($env, 'res.partner', $partnerId);

    WorkflowEngine::applyTransition($env, $inst, 'submit', [
        'submission_note' => 'Approve me',
    ]);

    $approval = $env->model('workflow.approval')->search([
        ['instance_id', '=', (int) $inst['id']],
        ['status', '=', 'pending'],
    ])->read()[0];

    WorkflowEngine::approve($env, $approval, approved: true, comment: 'Approved in test');

    $timeline = WorkflowHistory::recordTimeline(
        $env,
        'res.partner',
        $partnerId,
        (int) $inst['id'],
        (string) ($inst['definition_id'] ?? ''),
    );

    expect(collect($timeline)->pluck('kind'))->toContain('approved');
});

test('workflow history timeline includes rejected events', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('partners', $roots);
    $installer->install('workflow', $roots);

    $env = $installer->environment($roots);
    $partnerId = $env->model('res.partner')->create(['name' => 'Rejected History'])->ids()[0];

    WorkflowService::startForRecord($env, 'res.partner', $partnerId);
    $inst = WorkflowEngine::instanceForRecord($env, 'res.partner', $partnerId);
    $inst = WorkflowEngine::applyTransition($env, $inst, 'submit', ['submission_note' => 'No']);

    $approval = $env->model('workflow.approval')->search([
        ['instance_id', '=', (int) $inst['id']],
        ['status', '=', 'pending'],
    ])->read()[0];

    WorkflowEngine::approve($env, $approval, approved: false, comment: 'Rejected');

    $timeline = WorkflowHistory::recordTimeline(
        $env,
        'res.partner',
        $partnerId,
        (int) $inst['id'],
        (string) ($inst['definition_id'] ?? ''),
    );

    expect(collect($timeline)->pluck('kind'))->toContain('rejected');
});
