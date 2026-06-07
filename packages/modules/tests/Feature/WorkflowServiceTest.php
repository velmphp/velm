<?php

declare(strict_types=1);

use Velm\Modules\ModuleInstaller;
use Velm\Modules\Workflow\WorkflowDefinitions;
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

test('workflow service form context exposes statusbar for started partner workflow', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('partners', $roots);
    $installer->install('workflow', $roots);

    $env = $installer->environment($roots);
    $partner = $env->model('res.partner')->create(['name' => 'Context Partner']);
    $partnerId = $partner->ids()[0];

    WorkflowService::startForRecord($env, 'res.partner', $partnerId);
    $ctx = WorkflowService::formContext($env, 'res.partner', $partnerId);

    expect($ctx)->not->toBeNull()
        ->and($ctx['has_workflow'])->toBeTrue()
        ->and($ctx['started'])->toBeTrue()
        ->and($ctx['state'])->toBe('draft')
        ->and($ctx['statusbar'])->not->toBeEmpty()
        ->and(collect($ctx['transitions'])->pluck('key'))->toContain('submit');
});

test('workflow service statusbar marks done states before current', function (): void {
    $defn = [
        'states' => [
            ['key' => 'draft', 'label' => 'Draft', 'initial' => true],
            ['key' => 'review', 'label' => 'Review'],
            ['key' => 'done', 'label' => 'Done', 'final' => true],
        ],
        'transitions' => [],
    ];

    $bar = WorkflowService::statusbarFromDefn($defn, 'review');

    expect($bar[0]['done'])->toBeTrue()
        ->and($bar[1]['current'])->toBeTrue()
        ->and($bar[2]['done'])->toBeFalse();
});

test('workflow definitions seed partner demo is idempotent', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('partners', $roots);
    $installer->install('workflow', $roots);

    $env = $installer->environment($roots);
    WorkflowDefinitions::seedPartnerDemo($env);
    WorkflowDefinitions::seedPartnerDemo($env);

    $count = $env->model('workflow.definition')->search([['name', '=', 'Partner onboarding']])->count();

    expect($count)->toBe(1);
});

test('workflow service save definition updates row and deactivates siblings', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('partners', $roots);
    $installer->install('workflow', $roots);

    $env = $installer->environment($roots);
    $existing = $env->model('workflow.definition')->search([['model', '=', 'res.partner']], limit: 1)->read()[0];
    $definitionId = (int) $existing['id'];

    $defn = [
        'version' => 1,
        'model' => 'res.partner',
        'states' => [
            ['key' => 'draft', 'label' => 'Draft', 'initial' => true],
            ['key' => 'done', 'label' => 'Done', 'final' => true],
        ],
        'transitions' => [],
    ];

    WorkflowService::saveDefinition($env, $definitionId, [
        'name' => 'Updated Partner Flow',
        'description' => 'Saved via test',
        'model' => 'res.partner',
        'active' => true,
    ], $defn);

    $row = $env->browse('workflow.definition', [$definitionId])->read()[0];

    expect($row['name'])->toBe('Updated Partner Flow')
        ->and($row['description'])->toBe('Saved via test');
});

test('workflow engine reload instance returns fresh row', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('partners', $roots);
    $installer->install('workflow', $roots);

    $env = $installer->environment($roots);
    $partner = $env->model('res.partner')->create(['name' => 'Reload Partner']);
    $partnerId = $partner->ids()[0];

    WorkflowService::startForRecord($env, 'res.partner', $partnerId);
    $inst = WorkflowEngine::instanceForRecord($env, 'res.partner', $partnerId);

    $reloaded = WorkflowEngine::reloadInstance($env, (int) $inst['id']);

    expect($reloaded['id'])->toBe($inst['id'])
        ->and($reloaded['state'])->toBe('draft');
});

test('workflow service formContext returns not-started payload for manual workflows', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('partners', $roots);
    $installer->install('workflow', $roots);

    $env = $installer->environment($roots);
    $partnerId = $env->model('res.partner')->create(['name' => 'Manual Flow'])->ids()[0];

    $env->model('workflow.definition')->search([['model', '=', 'res.partner']])->write([
        'definition' => json_encode([
            'version' => 1,
            'model' => 'res.partner',
            'auto_start' => false,
            'states' => [
                ['key' => 'draft', 'label' => 'Draft', 'initial' => true],
                ['key' => 'done', 'label' => 'Done', 'final' => true],
            ],
            'transitions' => [],
        ], JSON_THROW_ON_ERROR),
    ]);

    $ctx = WorkflowService::formContext($env, 'res.partner', $partnerId);

    expect($ctx)->not->toBeNull()
        ->and($ctx['started'])->toBeFalse()
        ->and($ctx['can_start'])->toBeTrue()
        ->and($ctx['auto_start'])->toBeFalse();
});

test('workflow service formContext returns null without workflow instance model', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('partners', $roots);

    $env = $installer->environment($roots);

    expect(WorkflowService::formContext($env, 'res.partner', 1))->toBeNull();
});

test('workflow service inboxItems delegates to inbox list', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('partners', $roots);
    $installer->install('workflow', $roots);

    $env = $installer->environment($roots);

    expect(WorkflowService::inboxItems($env))->toBeArray();
});

test('workflow service statusbarFromDefn skips non-array state entries', function (): void {
    $bar = WorkflowService::statusbarFromDefn([
        'states' => [
            ['key' => 'draft', 'label' => 'Draft', 'initial' => true],
            'ignored',
            ['key' => 'done', 'label' => 'Done'],
        ],
    ], 'done');

    expect($bar)->toHaveCount(2)
        ->and($bar[1]['current'])->toBeTrue();
});

test('workflow service save definition deactivates sibling rows when active', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('partners', $roots);
    $installer->install('workflow', $roots);

    $env = $installer->environment($roots);
    $secondId = $env->model('workflow.definition')->create([
        'name' => 'Alternate Partner Flow',
        'model' => 'res.partner',
        'definition' => json_encode([
            'version' => 1,
            'model' => 'res.partner',
            'states' => [['key' => 'draft', 'label' => 'Draft', 'initial' => true]],
            'transitions' => [],
        ], JSON_THROW_ON_ERROR),
        'active' => true,
    ])->ids()[0];

    WorkflowService::saveDefinition($env, $secondId, [
        'name' => 'Alternate Partner Flow',
        'model' => 'res.partner',
        'active' => true,
    ], [
        'version' => 1,
        'model' => 'res.partner',
        'states' => [['key' => 'draft', 'label' => 'Draft', 'initial' => true]],
        'transitions' => [],
    ]);

    $activeCount = $env->model('workflow.definition')->search([
        ['model', '=', 'res.partner'],
        ['active', '=', true],
    ])->count();

    expect($activeCount)->toBe(1);
});

test('workflow service formContext exposes pending approvals for started workflow', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('partners', $roots);
    $installer->install('workflow', $roots);

    $env = $installer->environment($roots);
    $partnerId = $env->model('res.partner')->create(['name' => 'Pending Approval'])->ids()[0];

    WorkflowService::startForRecord($env, 'res.partner', $partnerId);
    $inst = WorkflowEngine::instanceForRecord($env, 'res.partner', $partnerId);
    WorkflowEngine::applyTransition($env, $inst, 'submit', ['submission_note' => 'Please review']);

    $ctx = WorkflowService::formContext($env, 'res.partner', $partnerId);

    expect($ctx['pending_approvals'])->not->toBeEmpty()
        ->and($ctx['pending_approvals'][0]['transition_label'])->not->toBe('');
});
