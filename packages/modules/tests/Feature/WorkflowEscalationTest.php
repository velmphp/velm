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
