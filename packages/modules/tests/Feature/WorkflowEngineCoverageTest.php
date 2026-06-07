<?php

declare(strict_types=1);

use Velm\Environment;
use Velm\Modules\ModuleInstaller;
use Velm\Modules\Workflow\WorkflowDefinitionError;
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

test('workflow engine activeDefinition returns null without workflow.definition model', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('partners', $roots);

    $env = $installer->environment($roots);

    expect(WorkflowEngine::activeDefinition($env, 'res.partner'))->toBeNull();
});

test('workflow engine start rejects model mismatch in definition', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('partners', $roots);
    $installer->install('workflow', $roots);

    $env = $installer->environment($roots);
    $partnerId = $env->model('res.partner')->create(['name' => 'Mismatch'])->ids()[0];
    $defnRow = WorkflowEngine::activeDefinition($env, 'res.partner');

    $defnRow['definition'] = json_encode([
        'version' => 1,
        'model' => 'res.country',
        'states' => [['key' => 'draft', 'label' => 'Draft', 'initial' => true]],
        'transitions' => [],
    ], JSON_THROW_ON_ERROR);

    expect(fn () => WorkflowEngine::start($env, 'res.partner', $partnerId, $defnRow))
        ->toThrow(WorkflowDefinitionError::class, 'model mismatch');
});

test('workflow engine applyTransition rejects wrong state', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('partners', $roots);
    $installer->install('workflow', $roots);

    $env = $installer->environment($roots);
    $partnerId = $env->model('res.partner')->create(['name' => 'Guarded'])->ids()[0];
    WorkflowService::startForRecord($env, 'res.partner', $partnerId);
    $inst = WorkflowEngine::instanceForRecord($env, 'res.partner', $partnerId);
    $inst['state'] = 'approved';

    expect(fn () => WorkflowEngine::applyTransition($env, $inst, 'submit'))
        ->toThrow(WorkflowDefinitionError::class, 'not allowed');
});

test('workflow engine reloadInstance throws for missing row', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('workflow', $roots);

    $env = $installer->environment($roots);

    expect(fn () => WorkflowEngine::reloadInstance($env, 999999))
        ->toThrow(WorkflowDefinitionError::class, 'not found');
});

test('workflow engine userMayActOnApproval checks assignee user match', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('workflow', $roots);

    $env = $installer->environment($roots);
    $userId = $env->uid;

    expect(WorkflowEngine::userMayActOnApproval($env, ['assignee_user_id' => $userId], $userId))->toBeTrue()
        ->and(WorkflowEngine::userMayActOnApproval($env, ['assignee_user_id' => $userId], 999999))->toBeFalse();
});

test('workflow engine resolveAssignees supports user assignee type', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('partners', $roots);
    $installer->install('workflow', $roots);

    $env = $installer->environment($roots);
    $partnerId = $env->model('res.partner')->create(['name' => 'Assignee User'])->ids()[0];
    $userId = $env->uid;

    expect(WorkflowEngine::resolveAssignees($env, [
        'res_model' => 'res.partner',
        'res_id' => $partnerId,
    ], [
        'approval' => ['assignee_type' => 'user', 'user_id' => $userId],
    ], $userId))->toEqual([['user_id' => $userId]]);
});

test('workflow engine applyTransition with required form field validates input', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('partners', $roots);
    $installer->install('workflow', $roots);

    $env = $installer->environment($roots);
    $partnerId = $env->model('res.partner')->create(['name' => 'Form Required'])->ids()[0];
    WorkflowService::startForRecord($env, 'res.partner', $partnerId);
    $inst = WorkflowEngine::instanceForRecord($env, 'res.partner', $partnerId);

    expect(fn () => WorkflowEngine::applyTransition($env, $inst, 'submit', []))
        ->toThrow(WorkflowDefinitionError::class, 'required');

    $updated = WorkflowEngine::applyTransition($env, $inst, 'submit', [
        'submission_note' => 'Provided',
    ]);

    expect($updated['pending_transition'])->toBe('submit');
});

test('workflow engine start throws when no active definition exists', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('partners', $roots);
    $installer->install('workflow', $roots);

    $env = $installer->environment($roots);
    $partnerId = $env->model('res.partner')->create(['name' => 'No Defn Start'])->ids()[0];

    $env->model('workflow.definition')->search([['model', '=', 'res.partner']])->write(['active' => false]);

    expect(fn () => WorkflowEngine::start($env, 'res.partner', $partnerId))
        ->toThrow(\Velm\Modules\Workflow\WorkflowDefinitionError::class, 'No active workflow');
});

test('workflow engine start returns existing instance without creating duplicate', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('partners', $roots);
    $installer->install('workflow', $roots);

    $env = $installer->environment($roots);
    $partnerId = $env->model('res.partner')->create(['name' => 'Existing Start'])->ids()[0];
    $first = WorkflowEngine::start($env, 'res.partner', $partnerId);
    $second = WorkflowEngine::start($env, 'res.partner', $partnerId);

    expect($second->ids()[0])->toBe($first->ids()[0]);
});

test('workflow engine applyTransition rejects users who cannot trigger transition', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('partners', $roots);
    $installer->install('workflow', $roots);

    $env = $installer->environment($roots);
    $partnerId = $env->model('res.partner')->create(['name' => 'Auto Guard'])->ids()[0];
    WorkflowService::startForRecord($env, 'res.partner', $partnerId);
    $inst = WorkflowEngine::instanceForRecord($env, 'res.partner', $partnerId);

    $env->model('workflow.definition')->search([['model', '=', 'res.partner']])->write([
        'definition' => json_encode([
            'version' => 1,
            'model' => 'res.partner',
            'states' => [
                ['key' => 'draft', 'label' => 'Draft', 'initial' => true],
                ['key' => 'done', 'label' => 'Done', 'final' => true],
            ],
            'transitions' => [[
                'key' => 'auto_done',
                'label' => 'Auto done',
                'from' => ['draft'],
                'to' => 'done',
                'kind' => 'automatic',
            ]],
        ], JSON_THROW_ON_ERROR),
    ]);

    $inst = WorkflowEngine::reloadInstance($env, (int) $inst['id']);

    expect(fn () => WorkflowEngine::applyTransition($env, $inst, 'auto_done', [], userId: 2))
        ->toThrow(\Velm\Exception\AccessDeniedException::class);
});

test('workflow engine resolveAssignees reads user id from record field', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('workflow', $roots);
    $installer->install('change_management', $roots);

    $env = $installer->environment($roots);
    $changeId = $env->model('it.change')->create([
        'name' => 'Field assignee',
        'change_type' => 'standard',
        'priority' => '2',
        'risk_level' => 'low',
        'implementer_id' => $env->uid,
    ])->ids()[0];

    $assignees = WorkflowEngine::resolveAssignees($env, [
        'res_model' => 'it.change',
        'res_id' => $changeId,
    ], [
        'approval' => ['assignee_type' => 'field', 'user_field' => 'implementer_id'],
    ], (int) $env->uid);

    expect($assignees)->toEqual([['user_id' => $env->uid]]);
});

test('workflow engine resolveAssignees returns empty when field assignee is unset', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('workflow', $roots);
    $installer->install('change_management', $roots);

    $env = $installer->environment($roots);
    $changeId = $env->model('it.change')->create([
        'name' => 'Missing implementer',
        'change_type' => 'standard',
        'priority' => '2',
        'risk_level' => 'low',
    ])->ids()[0];

    expect(WorkflowEngine::resolveAssignees($env, [
        'res_model' => 'it.change',
        'res_id' => $changeId,
    ], [
        'approval' => ['assignee_type' => 'field', 'user_field' => 'implementer_id'],
    ], (int) $env->uid))->toBe([]);
});

test('workflow engine applyTransition approval throws when no approvers resolved', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('partners', $roots);
    $installer->install('workflow', $roots);

    $env = $installer->environment($roots);

    $env->model('workflow.definition')->search([['model', '=', 'res.partner']])->write(['active' => false]);
    $env->model('workflow.definition')->create([
        'name' => 'No approver flow',
        'model' => 'res.partner',
        'definition' => json_encode([
            'version' => 1,
            'model' => 'res.partner',
            'states' => [
                ['key' => 'draft', 'label' => 'Draft', 'initial' => true],
                ['key' => 'done', 'label' => 'Done', 'final' => true],
            ],
            'transitions' => [[
                'key' => 'lonely',
                'label' => 'Lonely',
                'from' => ['draft'],
                'to' => 'done',
                'kind' => 'approval',
                'approval' => [
                    'strategy' => 'any',
                    'assignee_type' => 'field',
                    'user_field' => 'name',
                ],
            ]],
        ], JSON_THROW_ON_ERROR),
        'active' => true,
    ]);

    $partnerId = $env->model('res.partner')->create(['name' => 'Lonely Partner'])->ids()[0];
    WorkflowEngine::start($env, 'res.partner', $partnerId);
    $inst = WorkflowEngine::instanceForRecord($env, 'res.partner', $partnerId);

    expect(fn () => WorkflowEngine::applyTransition($env, $inst, 'lonely'))
        ->toThrow(\Velm\Modules\Workflow\WorkflowDefinitionError::class, 'No approvers resolved');
});

test('workflow engine applyTransition stores approval deadline when configured', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('partners', $roots);
    $installer->install('workflow', $roots);

    $env = $installer->environment($roots);
    $adminGid = $env->model('res.groups')->search([['name', '=', 'Admin']], limit: 1)->ids()[0];

    $env->model('workflow.definition')->search([['model', '=', 'res.partner']])->write(['active' => false]);
    $env->model('workflow.definition')->create([
        'name' => 'Deadline flow',
        'model' => 'res.partner',
        'definition' => json_encode([
            'version'  => 1,
            'model' => 'res.partner',
            'states' => [
                ['key' => 'draft', 'label' => 'Draft', 'initial' => true],
                ['key' => 'done', 'label' => 'Done', 'final' => true],
            ],
            'transitions' => [[
                'key' => 'deadline_submit',
                'label' => 'Deadline submit',
                'from' => ['draft'],
                'to' => 'done',
                'kind' => 'approval',
                'approval' => [
                    'strategy' => 'any',
                    'assignee_type' => 'group',
                    'group_id' => $adminGid,
                    'deadline_hours' => 24,
                ],
            ]],
        ], JSON_THROW_ON_ERROR),
        'active' => true,
    ]);

    $partnerId = $env->model('res.partner')->create(['name' => 'Deadline Partner'])->ids()[0];
    WorkflowEngine::start($env, 'res.partner', $partnerId);
    $inst = WorkflowEngine::instanceForRecord($env, 'res.partner', $partnerId);
    WorkflowEngine::applyTransition($env, $inst, 'deadline_submit');

    $approval = $env->model('workflow.approval')->search([
        ['instance_id', '=', (int) $inst['id']],
        ['status', '=', 'pending'],
    ])->read()[0];

    expect($approval['deadline_at'])->not->toBeNull();
});

test('workflow engine applyTransition ignores unknown form keys when splitting values', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('partners', $roots);
    $installer->install('workflow', $roots);

    $env = $installer->environment($roots);
    $partnerId = $env->model('res.partner')->create(['name' => 'Extra Form Keys'])->ids()[0];
    WorkflowEngine::start($env, 'res.partner', $partnerId);
    $inst = WorkflowEngine::instanceForRecord($env, 'res.partner', $partnerId);

    $updated = WorkflowEngine::applyTransition($env, $inst, 'submit', [
        'submission_note' => 'Valid note',
        'unexpected' => 'ignored',
    ]);

    expect($updated['pending_transition'])->toBe('submit');
});

test('workflow engine availableTransitions skips malformed and automatic entries', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('partners', $roots);
    $installer->install('workflow', $roots);

    $env = $installer->environment($roots);
    $partnerId = $env->model('res.partner')->create(['name' => 'Filtered Transitions'])->ids()[0];
    WorkflowEngine::start($env, 'res.partner', $partnerId);
    $inst = WorkflowEngine::instanceForRecord($env, 'res.partner', $partnerId);

    $defnRow = WorkflowEngine::activeDefinition($env, 'res.partner');
    $defn = json_decode((string) $defnRow['definition'], true, 512, JSON_THROW_ON_ERROR);
    $defn['transitions'] = [
        'broken-entry',
        [
            'key' => 'auto_done',
            'label' => 'Auto done',
            'from' => ['draft'],
            'to' => 'approved',
            'kind' => 'automatic',
        ],
        [
            'key' => 'submit',
            'label' => 'Submit for approval',
            'from' => ['draft'],
            'to' => 'approved',
            'kind' => 'approval',
        ],
    ];

    $env->browse('workflow.definition', [(int) $defnRow['id']])->write([
        'definition' => json_encode($defn, JSON_THROW_ON_ERROR),
    ]);

    $available = WorkflowEngine::availableTransitions($env, $inst, userId: 2);

    expect($available)->toHaveCount(1)
        ->and($available[0]['key'])->toBe('submit');
});

test('workflow engine approve denies users outside assignee group', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('partners', $roots);
    $installer->install('workflow', $roots);

    $env = $installer->environment($roots);
    $outsiderId = $env->model('res.users')->create([
        'name' => 'Outsider',
        'email' => 'outsider@test',
        'password' => 'secret',
    ])->ids()[0];

    $partnerId = $env->model('res.partner')->create(['name' => 'Outsider Partner'])->ids()[0];
    \Velm\Modules\Workflow\WorkflowService::startForRecord($env, 'res.partner', $partnerId);
    $inst = WorkflowEngine::instanceForRecord($env, 'res.partner', $partnerId);
    $inst = WorkflowEngine::applyTransition($env, $inst, 'submit', [
        'submission_note' => 'Needs review',
    ]);

    $approval = $env->model('workflow.approval')->search([
        ['instance_id', '=', (int) $inst['id']],
        ['status', '=', 'pending'],
    ])->read()[0];

    $outsiderEnv = new Environment($env->connection, $env->registry, uid: $outsiderId);

    expect(fn () => WorkflowEngine::approve($outsiderEnv, $approval, approved: true))
        ->toThrow(\Velm\Exception\AccessDeniedException::class);
});

test('workflow engine userMayActOnApproval allows group members', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('partners', $roots);
    $installer->install('workflow', $roots);

    $env = $installer->environment($roots);
    $adminGid = $env->model('res.groups')->search([['name', '=', 'Admin']], limit: 1)->ids()[0];
    $memberId = $env->model('res.users')->create([
        'name' => 'Group Member',
        'email' => 'member@test',
        'password' => 'secret',
        'group_ids' => [$adminGid],
    ])->ids()[0];

    $memberEnv = new Environment($env->connection, $env->registry, uid: $memberId);

    expect(WorkflowEngine::userMayActOnApproval($memberEnv, [
        'assignee_group_id' => $adminGid,
    ], $memberId))->toBeTrue()
        ->and(WorkflowEngine::userMayActOnApproval($memberEnv, [
            'assignee_user_id' => $memberId,
        ], $memberId))->toBeTrue();
});

test('workflow engine resolveAssignees returns empty when no group can be resolved', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('partners', $roots);
    $installer->install('workflow', $roots);

    $env = $installer->environment($roots);
    $partnerId = $env->model('res.partner')->create(['name' => 'No Group Partner'])->ids()[0];

    $env->model('res.groups')->search([['name', '=', 'Admin']])->unlink();

    expect(WorkflowEngine::resolveAssignees($env, [
        'res_model' => 'res.partner',
        'res_id' => $partnerId,
    ], [
        'approval' => ['assignee_type' => 'group', 'strategy' => 'any'],
    ], (int) $env->uid))->toBe([]);
});

test('workflow engine approvalsComplete returns false while queue remains', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('partners', $roots);
    $installer->install('workflow', $roots);

    $env = $installer->environment($roots);
    $partnerId = $env->model('res.partner')->create(['name' => 'Queue Partner'])->ids()[0];
    WorkflowService::startForRecord($env, 'res.partner', $partnerId);
    $instanceRow = WorkflowEngine::instanceForRecord($env, 'res.partner', $partnerId);
    $instanceRow['stage_data'] = json_encode(['_wf_queue' => [['user_id' => 99]]], JSON_THROW_ON_ERROR);

    $method = new ReflectionMethod(WorkflowEngine::class, 'approvalsComplete');
    $method->setAccessible(true);

    expect($method->invoke(null, $env, $instanceRow, [
        'key' => 'submit',
        'approval' => ['strategy' => 'sequential'],
    ]))->toBeFalse();
});

test('workflow engine approvalsComplete returns false when any approval rejected', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('partners', $roots);
    $installer->install('workflow', $roots);

    $env = $installer->environment($roots);
    $partnerId = $env->model('res.partner')->create(['name' => 'Rejected Partner'])->ids()[0];
    WorkflowEngine::start($env, 'res.partner', $partnerId);
    $instanceRow = WorkflowEngine::instanceForRecord($env, 'res.partner', $partnerId);
    $instanceId = (int) $instanceRow['id'];

    $env->model('workflow.approval')->create([
        'instance_id' => $instanceId,
        'transition_key' => 'submit',
        'status' => 'rejected',
        'sequence' => 1,
    ]);

    $method = new ReflectionMethod(WorkflowEngine::class, 'approvalsComplete');
    $method->setAccessible(true);

    expect($method->invoke(null, $env, $instanceRow, [
        'key' => 'submit',
        'approval' => ['strategy' => 'any'],
    ]))->toBeFalse();
});

test('workflow engine maybeAdvanceSequential creates next pending approval', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('partners', $roots);
    $installer->install('workflow', $roots);

    $env = $installer->environment($roots);
    $groupId = $env->model('res.groups')->create(['name' => 'Sequential Approvers'])->ids()[0];
    $secondUser = $env->model('res.users')->create([
        'name' => 'Second Approver',
        'email' => 'second-seq@test',
        'password' => 'secret',
        'group_ids' => [$groupId],
    ])->ids()[0];

    $env->model('workflow.definition')->search([['model', '=', 'res.partner']])->write(['active' => false]);
    $env->model('workflow.definition')->create([
        'name' => 'Sequential private probe',
        'model' => 'res.partner',
        'definition' => json_encode([
            'version' => 1,
            'model' => 'res.partner',
            'states' => [
                ['key' => 'draft', 'label' => 'Draft', 'initial' => true],
                ['key' => 'done', 'label' => 'Done', 'final' => true],
            ],
            'transitions' => [[
                'key' => 'seq_probe',
                'label' => 'Sequential probe',
                'from' => ['draft'],
                'to' => 'done',
                'kind' => 'approval',
                'approval' => [
                    'strategy' => 'sequential',
                    'assignee_type' => 'group',
                    'group_id' => $groupId,
                ],
            ]],
        ], JSON_THROW_ON_ERROR),
        'active' => true,
    ]);

    $partnerId = $env->model('res.partner')->create(['name' => 'Sequential Private'])->ids()[0];
    WorkflowEngine::start($env, 'res.partner', $partnerId);
    $instanceRow = WorkflowEngine::instanceForRecord($env, 'res.partner', $partnerId);
    $instanceId = (int) $instanceRow['id'];

    $instanceRow['stage_data'] = json_encode([
        '_wf_queue' => [['user_id' => $secondUser]],
    ], JSON_THROW_ON_ERROR);

    $method = new ReflectionMethod(WorkflowEngine::class, 'maybeAdvanceSequential');
    $method->setAccessible(true);
    $method->invoke(null, $env, $instanceRow, [
        'key' => 'seq_probe',
        'approval' => ['strategy' => 'sequential'],
    ]);

    $pending = $env->model('workflow.approval')->search([
        ['instance_id', '=', $instanceId],
        ['status', '=', 'pending'],
    ])->read();

    expect($pending)->toHaveCount(1)
        ->and((int) $pending[0]['assignee_user_id'])->toBe($secondUser);

    $updatedStage = json_decode(
        (string) $env->browse('workflow.instance', [$instanceId])->read(['stage_data'])[0]['stage_data'],
        true,
        512,
        JSON_THROW_ON_ERROR,
    );

    expect($updatedStage['_wf_queue'] ?? [])->toBe([]);
});
