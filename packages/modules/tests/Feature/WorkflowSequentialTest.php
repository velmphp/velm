<?php

declare(strict_types=1);

use Velm\Environment;
use Velm\Modules\ModuleInstaller;
use Velm\Modules\Workflow\WorkflowEngine;
use Velm\Modules\Tests\Support\ModuleRoots;
use Velm\Modules\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('sequential approval advances through each group member', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('partners', $roots);
    $installer->install('workflow', $roots);

    $env = $installer->environment($roots);
    $adminGid = $env->model('res.groups')->search([['name', '=', 'Admin']], limit: 1)->ids()[0];

    $bootstrapGroups = $env->browse('res.users', [1])->read()[0]['group_ids'] ?? [];

    if (! in_array($adminGid, $bootstrapGroups, true)) {
        $env->browse('res.users', [1])->write(['group_ids' => [...$bootstrapGroups, $adminGid]]);
    }

    $env->model('res.users')->create([
        'name' => 'Second Approver',
        'email' => 'approver2@test',
        'password' => 'secret',
        'group_ids' => [$adminGid],
    ]);

    $env->model('workflow.definition')->search([['model', '=', 'res.partner']])->write(['active' => false]);

    $definition = [
        'version' => 1,
        'model' => 'res.partner',
        'states' => [
            ['key' => 'draft', 'label' => 'Draft', 'initial' => true],
            ['key' => 'done', 'label' => 'Done', 'final' => true],
        ],
        'transitions' => [[
            'key' => 'seq_submit',
            'label' => 'Sequential submit',
            'from' => ['draft'],
            'to' => 'done',
            'kind' => 'approval',
            'approval' => [
                'strategy' => 'sequential',
                'assignee_type' => 'group',
                'group_id' => $adminGid,
            ],
            'form' => [
                'fields' => [['name' => 'note', 'type' => 'text', 'required' => true]],
            ],
        ]],
    ];

    $env->model('workflow.definition')->create([
        'name' => 'Sequential partner flow',
        'model' => 'res.partner',
        'definition' => json_encode($definition, JSON_THROW_ON_ERROR),
        'active' => true,
    ]);

    $partnerId = $env->model('res.partner')->create(['name' => 'Sequential Partner'])->ids()[0];
    WorkflowEngine::start($env, 'res.partner', $partnerId);
    $inst = WorkflowEngine::instanceForRecord($env, 'res.partner', $partnerId);

    $inst = WorkflowEngine::applyTransition($env, $inst, 'seq_submit', ['note' => 'Step one']);

    $pending = $env->model('workflow.approval')->search([
        ['instance_id', '=', (int) $inst['id']],
        ['status', '=', 'pending'],
    ])->read();

    expect($pending)->toHaveCount(1);

    while ($inst['state'] !== 'done') {
        $pendingNow = $env->model('workflow.approval')->search([
            ['instance_id', '=', (int) $inst['id']],
            ['status', '=', 'pending'],
        ])->read();

        expect($pendingNow)->not->toBeEmpty();

        $inst = WorkflowEngine::approve($env, $pendingNow[0], approved: true, userId: (int) ($pendingNow[0]['assignee_user_id'] ?? $env->uid));
    }

    expect($inst['state'])->toBe('done');
});

test('all strategy requires every group member approval', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('partners', $roots);
    $installer->install('workflow', $roots);

    $env = $installer->environment($roots);
    $adminGid = $env->model('res.groups')->search([['name', '=', 'Admin']], limit: 1)->ids()[0];

    $env->model('res.users')->create([
        'name' => 'Parallel Approver',
        'email' => 'parallel@test',
        'password' => 'secret',
        'group_ids' => [$adminGid],
    ]);

    $env->model('workflow.definition')->search([['model', '=', 'res.partner']])->write(['active' => false]);

    $definition = [
        'version' => 1,
        'model' => 'res.partner',
        'states' => [
            ['key' => 'draft', 'label' => 'Draft', 'initial' => true],
            ['key' => 'done', 'label' => 'Done', 'final' => true],
        ],
        'transitions' => [[
            'key' => 'all_submit',
            'label' => 'All must approve',
            'from' => ['draft'],
            'to' => 'done',
            'kind' => 'approval',
            'approval' => [
                'strategy' => 'all',
                'assignee_type' => 'group',
                'group_id' => $adminGid,
            ],
        ]],
    ];

    $env->model('workflow.definition')->create([
        'name' => 'All partner flow',
        'model' => 'res.partner',
        'definition' => json_encode($definition, JSON_THROW_ON_ERROR),
        'active' => true,
    ]);

    $partnerId = $env->model('res.partner')->create(['name' => 'All Strategy Partner'])->ids()[0];
    WorkflowEngine::start($env, 'res.partner', $partnerId);
    $inst = WorkflowEngine::instanceForRecord($env, 'res.partner', $partnerId);
    $inst = WorkflowEngine::applyTransition($env, $inst, 'all_submit');

    $pending = $env->model('workflow.approval')->search([
        ['instance_id', '=', (int) $inst['id']],
        ['status', '=', 'pending'],
    ])->read();

    expect(count($pending))->toBeGreaterThanOrEqual(2);

    $inst = WorkflowEngine::approve($env, $pending[0], approved: true, userId: Environment::SUPERUSER_ID);
    expect($inst['state'])->toBe('draft');

    $stillPending = $env->model('workflow.approval')->search([
        ['instance_id', '=', (int) $inst['id']],
        ['status', '=', 'pending'],
    ])->read();

    foreach ($stillPending as $approval) {
        $inst = WorkflowEngine::approve($env, $approval, approved: true, userId: Environment::SUPERUSER_ID);
    }

    expect($inst['state'])->toBe('done');
});
