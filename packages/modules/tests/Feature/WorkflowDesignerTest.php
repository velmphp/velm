<?php

declare(strict_types=1);

use Velm\Modules\ModuleInstaller;
use Velm\Modules\Workflow\WorkflowDesigner;
use Velm\Modules\Tests\TestCase;

uses(TestCase::class);

test('workflow designer lists models and fields', function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }

    $roots = [dirname(__DIR__, 2).'/modules'];
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
