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

test('partner workflow submit creates pending approval and approve completes transition', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('partners', $roots);
    $installer->install('workflow', $roots);

    $env = $installer->environment($roots);

    $partner = $env->model('res.partner')->create(['name' => 'Approve Partner']);
    $partnerId = $partner->ids()[0];

    WorkflowService::startForRecord($env, 'res.partner', $partnerId);
    $inst = WorkflowEngine::instanceForRecord($env, 'res.partner', $partnerId);
    expect($inst)->not->toBeNull();

    $inst = WorkflowEngine::applyTransition($env, $inst, 'submit', [
        'submission_note' => 'Ready for approval',
    ]);

    expect($inst['pending_transition'])->toBe('submit');

    $approvals = $env->model('workflow.approval')->search([
        ['instance_id', '=', (int) $inst['id']],
        ['status', '=', 'pending'],
    ])->read();

    expect($approvals)->not->toBeEmpty();

    $updated = WorkflowEngine::approve($env, $approvals[0], approved: true, comment: 'LGTM');

    expect($updated['state'])->toBe('approved')
        ->and($updated['pending_transition'] ?? null)->toBeIn([null, '']);
});

test('partner workflow approval rejection returns to rejected state', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('partners', $roots);
    $installer->install('workflow', $roots);

    $env = $installer->environment($roots);

    $partner = $env->model('res.partner')->create(['name' => 'Reject Partner']);
    $partnerId = $partner->ids()[0];

    WorkflowService::startForRecord($env, 'res.partner', $partnerId);
    $inst = WorkflowEngine::instanceForRecord($env, 'res.partner', $partnerId);

    $inst = WorkflowEngine::applyTransition($env, $inst, 'submit', [
        'submission_note' => 'Not good enough',
    ]);

    $approvals = $env->model('workflow.approval')->search([
        ['instance_id', '=', (int) $inst['id']],
        ['status', '=', 'pending'],
    ])->read();

    $updated = WorkflowEngine::approve($env, $approvals[0], approved: false, comment: 'No');

    expect($updated['state'])->toBe('rejected');
});

test('workflow engine transition by key resolves definition entry', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('workflow', $roots);
    $installer->install('change_management', $roots);

    $env = $installer->environment($roots);
    $defnRow = WorkflowEngine::activeDefinition($env, 'it.change');
    expect($defnRow)->not->toBeNull();

    $defn = \Velm\Modules\Workflow\WorkflowParser::parse((string) $defnRow['definition']);
    $tr = WorkflowEngine::transitionByKey($defn, 'submit_rfc');

    expect($tr['label'])->toBe('Submit RFC');
});

test('workflow engine initial state reads from definition', function (): void {
    $defn = [
        'states' => [
            ['key' => 'draft', 'label' => 'Draft', 'initial' => true],
            ['key' => 'done', 'label' => 'Done'],
        ],
        'transitions' => [],
    ];

    expect(WorkflowEngine::initialState($defn))->toBe('draft');
});

test('workflow engine available transitions empty while approval pending', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('partners', $roots);
    $installer->install('workflow', $roots);

    $env = $installer->environment($roots);
    $partnerId = $env->model('res.partner')->create(['name' => 'Pending Partner'])->ids()[0];

    WorkflowService::startForRecord($env, 'res.partner', $partnerId);
    $inst = WorkflowEngine::instanceForRecord($env, 'res.partner', $partnerId);
    $inst = WorkflowEngine::applyTransition($env, $inst, 'submit', [
        'submission_note' => 'Hold',
    ]);

    expect(WorkflowEngine::availableTransitions($env, $inst))->toBe([]);
});

test('workflow engine user may act on approval for assignee group member', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('partners', $roots);
    $installer->install('workflow', $roots);

    $env = $installer->environment($roots);
    $partnerId = $env->model('res.partner')->create(['name' => 'Approval Partner'])->ids()[0];

    WorkflowService::startForRecord($env, 'res.partner', $partnerId);
    $inst = WorkflowEngine::instanceForRecord($env, 'res.partner', $partnerId);
    $inst = WorkflowEngine::applyTransition($env, $inst, 'submit', [
        'submission_note' => 'Please review',
    ]);

    $approval = $env->model('workflow.approval')->search([
        ['instance_id', '=', (int) $inst['id']],
        ['status', '=', 'pending'],
    ])->read()[0];

    expect(WorkflowEngine::userMayActOnApproval($env, $approval, Environment::SUPERUSER_ID))->toBeTrue();
});

test('workflow engine direct user transition updates state', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('workflow', $roots);
    $installer->install('change_management', $roots);

    $env = $installer->environment($roots);
    $changeId = $env->model('it.change')->create([
        'name' => 'Direct Transition',
        'change_type' => 'standard',
        'priority' => '2',
        'risk_level' => 'low',
    ])->ids()[0];

    WorkflowService::startForRecord($env, 'it.change', $changeId);
    $inst = WorkflowEngine::instanceForRecord($env, 'it.change', $changeId);
    $updated = WorkflowEngine::applyTransition($env, $inst, 'submit_rfc', [
        'business_justification' => 'Required for coverage test',
    ]);

    expect($updated['state'])->not->toBe('draft');
});

test('workflow engine blocks automatic transitions for normal users', function (): void {
    expect(WorkflowEngine::userMayTrigger(['kind' => 'automatic'], 2))->toBeFalse()
        ->and(WorkflowEngine::userMayTrigger(['kind' => 'user'], 2))->toBeTrue();
});

test('workflow engine resolve assignees returns group from approval config', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('workflow', $roots);

    $env = $installer->environment($roots);
    $groupId = $env->model('res.groups')->search([['name', '=', 'Admin']], limit: 1)->ids()[0];

    $assignees = WorkflowEngine::resolveAssignees($env, ['id' => 1], [
        'approval' => ['assignee_type' => 'group', 'group_id' => $groupId],
    ], 1);

    expect($assignees)->toEqual([['group_id' => $groupId]]);
});

test('workflow engine resolveAssignees requires user id for user assignee type', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('partners', $roots);
    $installer->install('workflow', $roots);

    $env = $installer->environment($roots);
    $partnerId = $env->model('res.partner')->create(['name' => 'User Assignee'])->ids()[0];

    expect(fn () => WorkflowEngine::resolveAssignees($env, [
        'id' => 1,
        'res_model' => 'res.partner',
        'res_id' => $partnerId,
    ], [
        'approval' => ['assignee_type' => 'user', 'user_id' => 0],
    ], 1))->toThrow(\Velm\Modules\Workflow\WorkflowDefinitionError::class);
});

test('workflow engine transition ui exposes form metadata', function (): void {
    $ui = WorkflowEngine::transitionUi([
        'key' => 'approve',
        'label' => 'Approve',
        'kind' => 'approval',
        'form' => ['title' => 'Approval form', 'fields' => [['name' => 'comment']]],
    ]);

    expect($ui['key'])->toBe('approve')
        ->and($ui['form_title'])->toBe('Approval form')
        ->and($ui['form_fields'])->toHaveCount(1);
});
