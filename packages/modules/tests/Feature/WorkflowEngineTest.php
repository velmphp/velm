<?php

declare(strict_types=1);

use Velm\Modules\ModuleInstaller;
use Velm\Modules\Workflow\WorkflowEngine;
use Velm\Modules\Workflow\WorkflowParser;
use Velm\Modules\Workflow\WorkflowSchema;
use Velm\Modules\Workflow\WorkflowService;
use Velm\Modules\Tests\Support\ModuleRoots;
use Velm\Modules\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('workflow schema validates ICT change definition', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('workflow', $roots);
    $installer->install('change_management', $roots);

    $env = $installer->environment($roots);
    $rows = $env->model('workflow.definition')->search([['name', '=', 'ICT Change lifecycle']])->read(['definition']);

    expect($rows)->not->toBeEmpty();

    $defn = WorkflowParser::parse((string) $rows[0]['definition']);
    WorkflowSchema::validate($defn, $env->registry);

    expect($defn['model'])->toBe('it.change')
        ->and(collect($defn['states'])->pluck('key')->all())->toContain('cab_review', 'closed');
});

test('change request workflow runs submit and approval path', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('workflow', $roots);
    $installer->install('change_management', $roots);

    $env = $installer->environment($roots);
    $change = $env->model('it.change')->create([
        'name' => 'Firewall rule update',
        'reference' => 'CHG-001',
        'business_justification' => 'Required for new service',
    ]);
    $changeId = $change->ids()[0];

    WorkflowService::startForRecord($env, 'it.change', $changeId);
    $inst = WorkflowEngine::instanceForRecord($env, 'it.change', $changeId);

    expect($inst)->not->toBeNull()
        ->and($inst['state'])->toBe('draft');

    $inst = WorkflowEngine::applyTransition($env, $inst, 'submit_rfc', [
        'business_justification' => 'Required for new service',
    ]);

    expect($inst['state'])->toBe('submitted');

    $inst = WorkflowEngine::applyTransition($env, $inst, 'start_risk_review');

    expect($inst['state'])->toBe('risk_review');
});

test('workflow install seeds partner demo definition', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('partners', $roots);
    $installer->install('workflow', $roots);

    $env = $installer->environment($roots);
    $count = $env->model('workflow.definition')->search([['model', '=', 'res.partner']])->count();

    expect($count)->toBeGreaterThanOrEqual(1);
});
