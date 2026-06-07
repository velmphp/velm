<?php

declare(strict_types=1);

use Velm\Environment;
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

/**
 * @return array<string, mixed>
 */
function approveAllPending(Environment $env, array $inst): array
{
    while (true) {
        $approvals = $env->model('workflow.approval')->search([
            ['instance_id', '=', (int) $inst['id']],
            ['status', '=', 'pending'],
        ])->read();

        if ($approvals === []) {
            break;
        }

        foreach ($approvals as $approval) {
            $inst = WorkflowEngine::approve($env, $approval, approved: true, comment: 'Approved', userId: Environment::SUPERUSER_ID);
        }
    }

    return $inst;
}

test('ict change workflow runs full lifecycle through closed state', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('workflow', $roots);
    $installer->install('change_management', $roots);

    $env = $installer->environment($roots);
    $changeId = $env->model('it.change')->create([
        'name' => 'Full lifecycle change',
        'reference' => 'CHG-FULL',
        'business_justification' => 'Coverage path',
        'risk_level' => 'low',
    ])->ids()[0];

    WorkflowService::startForRecord($env, 'it.change', $changeId);
    $inst = WorkflowEngine::instanceForRecord($env, 'it.change', $changeId);

    $inst = WorkflowEngine::applyTransition($env, $inst, 'submit_rfc', [
        'business_justification' => 'Coverage path',
    ]);
    expect($inst['state'])->toBe('submitted');

    $inst = WorkflowEngine::applyTransition($env, $inst, 'start_risk_review');
    expect($inst['state'])->toBe('risk_review');

    $inst = WorkflowEngine::applyTransition($env, $inst, 'complete_risk', [
        'risk_level' => 'low',
        'risk_notes' => 'Low risk',
    ]);
    $inst = approveAllPending($env, $inst);
    expect($inst['state'])->toBe('cab_review');

    $inst = WorkflowEngine::applyTransition($env, $inst, 'cab_decision', [
        'cab_notes' => 'Proceed',
    ]);
    $inst = approveAllPending($env, $inst);
    expect($inst['state'])->toBe('approved');

    $inst = WorkflowEngine::applyTransition($env, $inst, 'schedule', [
        'planned_start' => '2026-06-01 10:00:00',
        'planned_end' => '2026-06-01 12:00:00',
    ]);
    expect($inst['state'])->toBe('scheduled');

    $inst = WorkflowEngine::applyTransition($env, $inst, 'start_implementation');
    expect($inst['state'])->toBe('implementing');

    $inst = WorkflowEngine::applyTransition($env, $inst, 'complete_implementation', [
        'implementation_notes' => 'Deployed successfully',
    ]);
    expect($inst['state'])->toBe('validating');

    $inst = WorkflowEngine::applyTransition($env, $inst, 'close_change', [
        'pir_outcome' => 'Successful',
    ]);
    $inst = approveAllPending($env, $inst);

    expect($inst['state'])->toBe('closed')
        ->and(WorkflowEngine::availableTransitions($env, $inst))->not->toBeEmpty();
});

test('workflow start returns existing instance for duplicate start', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('partners', $roots);
    $installer->install('workflow', $roots);

    $env = $installer->environment($roots);
    $partnerId = $env->model('res.partner')->create(['name' => 'Dup Start'])->ids()[0];

    $first = WorkflowEngine::start($env, 'res.partner', $partnerId);
    $second = WorkflowEngine::start($env, 'res.partner', $partnerId);

    expect($first->ids()[0])->toBe($second->ids()[0]);
});

test('workflow engine blocks second transition while approval pending', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('partners', $roots);
    $installer->install('workflow', $roots);

    $env = $installer->environment($roots);
    $partnerId = $env->model('res.partner')->create(['name' => 'Pending Gate'])->ids()[0];

    WorkflowService::startForRecord($env, 'res.partner', $partnerId);
    $inst = WorkflowEngine::instanceForRecord($env, 'res.partner', $partnerId);
    $inst = WorkflowEngine::applyTransition($env, $inst, 'submit', ['submission_note' => 'Hold']);

    expect(fn () => WorkflowEngine::applyTransition($env, $inst, 'reset'))
        ->toThrow(\Velm\Modules\Workflow\WorkflowDefinitionError::class);
});

test('workflow engine rejects acting on completed approval', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('partners', $roots);
    $installer->install('workflow', $roots);

    $env = $installer->environment($roots);
    $partnerId = $env->model('res.partner')->create(['name' => 'Done Approval'])->ids()[0];

    WorkflowService::startForRecord($env, 'res.partner', $partnerId);
    $inst = WorkflowEngine::instanceForRecord($env, 'res.partner', $partnerId);
    $inst = WorkflowEngine::applyTransition($env, $inst, 'submit', ['submission_note' => 'Go']);
    $approval = $env->model('workflow.approval')->search([
        ['instance_id', '=', (int) $inst['id']],
        ['status', '=', 'pending'],
    ])->read()[0];

    WorkflowEngine::approve($env, $approval, approved: true);

    $completed = $env->browse('workflow.approval', [(int) $approval['id']])->read()[0];

    expect(fn () => WorkflowEngine::approve($env, $completed, approved: true))
        ->toThrow(\Velm\Modules\Workflow\WorkflowDefinitionError::class);
});

test('workflow engine rejects missing required transition form fields', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('partners', $roots);
    $installer->install('workflow', $roots);

    $env = $installer->environment($roots);
    $partnerId = $env->model('res.partner')->create(['name' => 'Validate Form'])->ids()[0];

    WorkflowService::startForRecord($env, 'res.partner', $partnerId);
    $inst = WorkflowEngine::instanceForRecord($env, 'res.partner', $partnerId);

    expect(fn () => WorkflowEngine::applyTransition($env, $inst, 'submit', []))
        ->toThrow(\Velm\Modules\Workflow\WorkflowDefinitionError::class);
});
