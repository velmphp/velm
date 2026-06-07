<?php

declare(strict_types=1);

use Velm\Modules\ModuleInstaller;
use Velm\Modules\Workflow\WorkflowDesigner;
use Velm\Modules\Tests\Support\ModuleRoots;
use Velm\Modules\Tests\TestCase;

uses(TestCase::class);

test('workflow designer lists models and fields', function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }

    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('workflow', $roots);
    $installer->install('change_management', $roots);

    $env = $installer->environment($roots);
    $models = WorkflowDesigner::listModels($env);

    expect(collect($models)->pluck('value')->all())->toContain('it.change');

    $fields = WorkflowDesigner::listModelFields($env, 'it.change');

    expect(collect($fields)->pluck('name')->all())->toContain('name', 'business_justification');
});

test('workflow designer lists groups and users for builder api', function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }

    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('workflow', $roots);

    $env = $installer->environment($roots);

    expect(WorkflowDesigner::listGroups($env))->not->toBeEmpty()
        ->and(WorkflowDesigner::listUsers($env))->not->toBeEmpty();
});

test('workflow designer returns empty fields for unknown model', function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }

    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base']);
    $env = $installer->environment($roots);

    expect(WorkflowDesigner::listModelFields($env, 'missing.model'))->toBe([]);
});

test('workflow designer builder config hydrates existing definition row', function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }

    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('workflow', $roots);
    $installer->install('change_management', $roots);

    $env = $installer->environment($roots);
    $row = $env->model('workflow.definition')->search([['name', '=', 'ICT Change lifecycle']], limit: 1)->read()[0];

    $config = WorkflowDesigner::builderConfig($env, $row);

    expect($config['workflowId'])->toBe((int) $row['id'])
        ->and($config['meta']['model'])->toBe('it.change')
        ->and($config['recordFields'])->not->toBeEmpty()
        ->and($config['definition']['states'])->not->toBeEmpty();
});

test('workflow designer builderConfig returns defaults without definition row', function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }

    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('workflow', $roots);

    $env = $installer->environment($roots);
    $config = WorkflowDesigner::builderConfig($env);

    expect($config['workflowId'])->toBeNull()
        ->and($config['recordFields'])->toBe([])
        ->and($config['models'])->not->toBeEmpty();
});

test('workflow designer listModelFields skips readonly fields', function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }

    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('partners', $roots);

    $env = $installer->environment($roots);
    $fields = WorkflowDesigner::listModelFields($env, 'res.partner');

    expect(collect($fields)->pluck('name'))->not->toContain('id', 'display_name');
});

test('workflow designer listGroups and listUsers return empty without models', function (): void {
    $env = \Velm\Registry::using(function (\Velm\Registry $registry): \Velm\Environment {
        return new \Velm\Environment(\Velm\Database\PdoConnection::sqliteMemory(), $registry);
    });

    expect(WorkflowDesigner::listGroups($env))->toBe([])
        ->and(WorkflowDesigner::listUsers($env))->toBe([]);
});
