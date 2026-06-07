<?php

declare(strict_types=1);

use Velm\Modules\Dashboard\DashboardData;
use Velm\Modules\ModuleInstaller;
use Velm\Modules\Workflow\Dashboard\PendingApprovalsWidget;
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

test('workflow dashboard data file registers pending approvals widget', function (): void {
    $path = dirname(__DIR__, 3).'/modules/modules/workflow/dashboard.php';
    $data = require $path;

    expect($data)->toBeInstanceOf(DashboardData::class);

    $widgets = $data->widgets();

    expect($widgets)->toHaveCount(1)
        ->and($widgets[0]->id)->toBe('workflow_pending_approvals')
        ->and($widgets[0]->model)->toBe('workflow.approval');
});

test('pending approvals widget resolves inbox items and links', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('partners', $roots);
    $installer->install('workflow', $roots);

    $env = $installer->environment($roots);
    $partnerId = $env->model('res.partner')->create(['name' => 'Widget Partner'])->ids()[0];

    WorkflowService::startForRecord($env, 'res.partner', $partnerId);
    $inst = WorkflowEngine::instanceForRecord($env, 'res.partner', $partnerId);
    WorkflowEngine::applyTransition($env, $inst, 'submit', ['submission_note' => 'Review']);

    $resolved = PendingApprovalsWidget::resolve($env);

    expect($resolved['items'])->not->toBeEmpty()
        ->and($resolved['href'])->toBe('/web/workflow/inbox')
        ->and($resolved['action_label'])->toBe('Open inbox')
        ->and($resolved['empty_label'])->toBe('No approvals waiting for you.');
});

test('pending approvals widget returns empty label when inbox is clear', function (): void {
    $roots = ModuleRoots::forTests();
    $installer = new ModuleInstaller;
    $installer->installBootstrap($roots, ['base', 'admin']);
    $installer->install('workflow', $roots);

    $env = $installer->environment($roots);
    $resolved = PendingApprovalsWidget::resolve($env);

    expect($resolved['items'])->toBe([])
        ->and($resolved['empty_label'])->toBe('No approvals waiting for you.');
});
