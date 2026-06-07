<?php

declare(strict_types=1);

use Velm\Modules\ModuleInstaller;
use Velm\Modules\Workflow\WorkflowSyncHooks;
use Velm\Modules\Tests\Support\ModuleRoots;
use Velm\Modules\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('workflow sync hooks seed definitions and cron on sync', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('partners', $roots);
    $installer->install('workflow', $roots);
    $installer->install('change_management', $roots);

    $env = $installer->environment($roots);

    WorkflowSyncHooks::sync($env);

    expect($env->model('workflow.definition')->search()->count())->toBeGreaterThan(0)
        ->and($env->model('ir.cron')->search([['name', '=', 'Workflow approval escalation']])->count())->toBe(1);
});
