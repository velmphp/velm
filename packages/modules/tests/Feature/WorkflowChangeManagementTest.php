<?php

declare(strict_types=1);

use Velm\Modules\ModuleInstaller;
use Velm\Modules\Workflow\WorkflowEngine;
use Velm\Modules\Workflow\WorkflowService;
use Velm\Modules\Tests\Support\ModuleRoots;
use Velm\Modules\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('ict change workflow progresses through risk review and cab approval', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('workflow', $roots);
    $installer->install('change_management', $roots);

    $env = $installer->environment($roots);
    $change = $env->model('it.change')->create([
        'name' => 'Database patch',
        'reference' => 'CHG-100',
        'business_justification' => 'Security fix',
        'risk_level' => 'medium',
    ]);
    $changeId = $change->ids()[0];

    WorkflowService::startForRecord($env, 'it.change', $changeId);
    $inst = WorkflowEngine::instanceForRecord($env, 'it.change', $changeId);

    $inst = WorkflowEngine::applyTransition($env, $inst, 'submit_rfc', [
        'business_justification' => 'Security fix',
    ]);
    expect($inst['state'])->toBe('submitted');

    $inst = WorkflowEngine::applyTransition($env, $inst, 'start_risk_review');
    expect($inst['state'])->toBe('risk_review');

    $inst = WorkflowEngine::applyTransition($env, $inst, 'complete_risk', [
        'risk_level' => 'medium',
        'risk_notes' => 'Acceptable risk profile',
    ]);

    expect($inst['pending_transition'] ?? null)->not->toBeNull();

    $approval = $env->model('workflow.approval')->search([
        ['instance_id', '=', (int) $inst['id']],
        ['status', '=', 'pending'],
    ])->read()[0] ?? null;

    expect($approval)->not->toBeNull();

    $inst = WorkflowEngine::approve($env, $approval, approved: true, comment: 'Risk OK');
    expect($inst['state'])->toBe('cab_review');
});

test('workflow service inbox lists pending approvals', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('partners', $roots);
    $installer->install('workflow', $roots);

    $env = $installer->environment($roots);
    $partner = $env->model('res.partner')->create(['name' => 'Inbox Partner']);
    WorkflowService::startForRecord($env, 'res.partner', $partner->ids()[0]);
    $inst = WorkflowEngine::instanceForRecord($env, 'res.partner', $partner->ids()[0]);
    WorkflowEngine::applyTransition($env, $inst, 'submit', ['submission_note' => 'Please review']);

    $items = WorkflowService::inboxItems($env);

    expect($items)->not->toBeEmpty();
});
