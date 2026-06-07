<?php

declare(strict_types=1);

use Velm\Modules\ModuleInstaller;
use Velm\Modules\Workflow\WorkflowEngine;
use Velm\Modules\Workflow\WorkflowRuntime;
use Velm\Modules\Tests\Support\ModuleRoots;
use Velm\Modules\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('workflow runtime auto starts partner records when definition has auto_start', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('partners', $roots);
    $installer->install('workflow', $roots);

    $env = $installer->environment($roots);
    $partnerId = $env->model('res.partner')->create(['name' => 'Auto Start Partner'])->ids()[0];

    expect(WorkflowEngine::instanceForRecord($env, 'res.partner', $partnerId))->toBeNull();

    WorkflowRuntime::maybeAutoStart($env, 'res.partner', $partnerId);

    expect(WorkflowEngine::instanceForRecord($env, 'res.partner', $partnerId))->not->toBeNull();
});

test('workflow runtime backfill auto start creates missing instances', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('partners', $roots);
    $installer->install('workflow', $roots);

    $env = $installer->environment($roots);

    $minimalDefn = [
        'version' => 1,
        'model' => 'res.partner',
        'states' => [
            ['key' => 'draft', 'label' => 'Draft', 'initial' => true],
            ['key' => 'done', 'label' => 'Done', 'final' => true],
        ],
        'transitions' => [],
    ];

    $env->model('workflow.definition')->search([['model', '=', 'res.partner']])->write([
        'definition' => json_encode([...$minimalDefn, 'auto_start' => false], JSON_THROW_ON_ERROR),
    ]);

    $partnerId = $env->model('res.partner')->create(['name' => 'Backfill Partner'])->ids()[0];

    $env->model('workflow.definition')->search([['model', '=', 'res.partner']])->write([
        'definition' => json_encode([...$minimalDefn, 'auto_start' => true], JSON_THROW_ON_ERROR),
    ]);

    expect(WorkflowEngine::instanceForRecord($env, 'res.partner', $partnerId))->toBeNull();

    $count = WorkflowRuntime::backfillAutoStart($env, 'res.partner');

    expect($count)->toBeGreaterThanOrEqual(1)
        ->and(WorkflowEngine::instanceForRecord($env, 'res.partner', $partnerId))->not->toBeNull();
});

test('workflow runtime maybeAutoStart skips when auto_start is disabled', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('partners', $roots);
    $installer->install('workflow', $roots);

    $env = $installer->environment($roots);
    $partnerId = $env->model('res.partner')->create(['name' => 'Manual Start'])->ids()[0];

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

    WorkflowRuntime::maybeAutoStart($env, 'res.partner', $partnerId);

    expect(WorkflowEngine::instanceForRecord($env, 'res.partner', $partnerId))->toBeNull();
});

test('workflow runtime maybeAutoStart is no-op without workflow models', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('partners', $roots);

    $env = $installer->environment($roots);
    $partnerId = $env->model('res.partner')->create(['name' => 'No workflow'])->ids()[0];

    WorkflowRuntime::maybeAutoStart($env, 'res.partner', $partnerId);

    expect(WorkflowEngine::instanceForRecord($env, 'res.partner', $partnerId))->toBeNull();
});

test('workflow runtime maybeAutoStart skips when instance already exists', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('partners', $roots);
    $installer->install('workflow', $roots);

    $env = $installer->environment($roots);
    $partnerId = $env->model('res.partner')->create(['name' => 'Existing Instance'])->ids()[0];

    WorkflowRuntime::maybeAutoStart($env, 'res.partner', $partnerId);
    $first = WorkflowEngine::instanceForRecord($env, 'res.partner', $partnerId);

    WorkflowRuntime::maybeAutoStart($env, 'res.partner', $partnerId);
    $second = WorkflowEngine::instanceForRecord($env, 'res.partner', $partnerId);

    expect($first['id'])->toBe($second['id']);
});

test('workflow runtime maybeAutoStart swallows invalid definition errors', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('partners', $roots);
    $installer->install('workflow', $roots);

    $env = $installer->environment($roots);
    $partnerId = $env->model('res.partner')->create(['name' => 'Bad Defn'])->ids()[0];

    $env->model('workflow.definition')->search([['model', '=', 'res.partner']])->write([
        'definition' => '{"version":1,"model":"res.partner","states":[],"transitions":[]}',
    ]);

    WorkflowRuntime::maybeAutoStart($env, 'res.partner', $partnerId);

    expect(WorkflowEngine::instanceForRecord($env, 'res.partner', $partnerId))->toBeNull();
});

test('workflow runtime backfill returns zero when no auto_start definition', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('partners', $roots);
    $installer->install('workflow', $roots);

    $env = $installer->environment($roots);

    expect(WorkflowRuntime::backfillAutoStart($env, 'res.country'))->toBe(0);
});

test('workflow runtime maybeAutoStart returns when no active definition exists', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('partners', $roots);
    $installer->install('workflow', $roots);

    $env = $installer->environment($roots);
    $partnerId = $env->model('res.partner')->create(['name' => 'No Defn'])->ids()[0];

    $env->model('workflow.definition')->search([['model', '=', 'res.partner']])->write(['active' => false]);

    WorkflowRuntime::maybeAutoStart($env, 'res.partner', $partnerId);

    expect(WorkflowEngine::instanceForRecord($env, 'res.partner', $partnerId))->toBeNull();
});

test('workflow runtime backfill returns zero when auto_start is disabled', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('partners', $roots);
    $installer->install('workflow', $roots);

    $env = $installer->environment($roots);

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

    expect(WorkflowRuntime::backfillAutoStart($env, 'res.partner'))->toBe(0);
});

test('workflow runtime maybeAutoStart swallows engine failures without breaking caller', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('partners', $roots);
    $installer->install('workflow', $roots);

    $env = $installer->environment($roots);
    $partnerId = $env->model('res.partner')->create(['name' => 'Throwing Start'])->ids()[0];

    $env->model('workflow.definition')->search([['model', '=', 'res.partner']])->write([
        'definition' => json_encode([
            'version' => 1,
            'model' => 'res.partner',
            'auto_start' => true,
            'states' => [],
            'transitions' => [],
        ], JSON_THROW_ON_ERROR),
    ]);

    WorkflowRuntime::maybeAutoStart($env, 'res.partner', $partnerId);

    expect(WorkflowEngine::instanceForRecord($env, 'res.partner', $partnerId))->toBeNull();
});

test('workflow runtime backfill skips records that already have instances', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('partners', $roots);
    $installer->install('workflow', $roots);

    $env = $installer->environment($roots);
    $existingId = $env->model('res.partner')->create(['name' => 'Has Instance'])->ids()[0];
    WorkflowRuntime::maybeAutoStart($env, 'res.partner', $existingId);

    $env->model('workflow.definition')->search([['model', '=', 'res.partner']])->write([
        'definition' => json_encode([
            'version' => 1,
            'model' => 'res.partner',
            'auto_start' => true,
            'states' => [
                ['key' => 'draft', 'label' => 'Draft', 'initial' => true],
                ['key' => 'done', 'label' => 'Done', 'final' => true],
            ],
            'transitions' => [],
        ], JSON_THROW_ON_ERROR),
    ]);

    $newId = $env->model('res.partner')->create(['name' => 'Needs Instance'])->ids()[0];

    expect(WorkflowEngine::instanceForRecord($env, 'res.partner', $newId))->toBeNull();

    $count = WorkflowRuntime::backfillAutoStart($env, 'res.partner');

    expect($count)->toBeGreaterThanOrEqual(1)
        ->and(WorkflowEngine::instanceForRecord($env, 'res.partner', $newId))->not->toBeNull()
        ->and(WorkflowEngine::instanceForRecord($env, 'res.partner', $existingId))->not->toBeNull();
});
