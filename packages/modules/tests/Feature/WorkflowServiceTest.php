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
