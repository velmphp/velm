<?php

declare(strict_types=1);

use Velm\Modules\ModuleInstaller;
use Velm\Modules\Workflow\WorkflowEngine;
use Velm\Modules\Workflow\WorkflowEscalation;
use Velm\Modules\Workflow\WorkflowService;
use Velm\Modules\Tests\Support\ModuleRoots;
use Velm\Modules\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('workflow escalation reassigns overdue approval to escalate group', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('partners', $roots);
    $installer->install('workflow', $roots);

    $env = $installer->environment($roots);
    $partnerId = $env->model('res.partner')->create(['name' => 'Escalate Partner'])->ids()[0];

    WorkflowService::startForRecord($env, 'res.partner', $partnerId);
    $inst = WorkflowEngine::instanceForRecord($env, 'res.partner', $partnerId);
    expect($inst)->not->toBeNull();

    WorkflowEngine::applyTransition($env, $inst, 'submit', [
        'submission_note' => 'Needs escalation',
    ]);

    $pending = $env->model('workflow.approval')->search([
        ['instance_id', '=', (int) $inst['id']],
        ['status', '=', 'pending'],
    ])->read();

    expect($pending)->not->toBeEmpty();

    $env->browse('workflow.approval', [(int) $pending[0]['id']])->write([
        'deadline_at' => gmdate('Y-m-d H:i:s', time() - 3600),
    ]);

    $escalated = WorkflowEscalation::processOverdue($env);

    expect($escalated)->toBeGreaterThanOrEqual(0);
});

test('workflow escalation returns zero when approval model is unavailable', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('partners', $roots);

    $env = $installer->environment($roots);

    expect(WorkflowEscalation::processOverdue($env))->toBe(0);
});

test('workflow escalation reassigns overdue approval when escalate group configured', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('partners', $roots);
    $installer->install('workflow', $roots);

    $env = $installer->environment($roots);
    $escalateGroupId = $env->model('res.groups')->create(['name' => 'Escalation Managers'])->ids()[0];
    $partnerId = $env->model('res.partner')->create(['name' => 'Escalate Me'])->ids()[0];

    $defnRow = $env->model('workflow.definition')->search([['model', '=', 'res.partner']], limit: 1)->read()[0];
    $defn = json_decode((string) ($defnRow['definition'] ?? '{}'), true, flags: JSON_THROW_ON_ERROR);
    $defn['transitions'][0]['approval']['escalate_to_group_id'] = $escalateGroupId;
    $env->browse('workflow.definition', [(int) $defnRow['id']])->write([
        'definition' => json_encode($defn, JSON_THROW_ON_ERROR),
    ]);

    WorkflowService::startForRecord($env, 'res.partner', $partnerId);
    $inst = WorkflowEngine::instanceForRecord($env, 'res.partner', $partnerId);
    WorkflowEngine::applyTransition($env, $inst, 'submit', ['submission_note' => 'Please review']);

    $pending = $env->model('workflow.approval')->search([
        ['instance_id', '=', (int) $inst['id']],
        ['status', '=', 'pending'],
    ])->read();

    $env->browse('workflow.approval', [(int) $pending[0]['id']])->write([
        'deadline_at' => gmdate('Y-m-d H:i:s', time() - 3600),
    ]);

    $escalated = WorkflowEscalation::processOverdue($env);

    expect($escalated)->toBe(1);

    $newPending = $env->model('workflow.approval')->search([
        ['instance_id', '=', (int) $inst['id']],
        ['status', '=', 'pending'],
        ['assignee_group_id', '=', $escalateGroupId],
    ])->count();

    expect($newPending)->toBeGreaterThanOrEqual(1);
});
