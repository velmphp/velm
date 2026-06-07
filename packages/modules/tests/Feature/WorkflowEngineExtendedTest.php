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
