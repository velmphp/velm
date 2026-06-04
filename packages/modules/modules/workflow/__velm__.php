<?php

declare(strict_types=1);

use Velm\Modules\Manifest;
use Velm\Modules\Workflow\Models\WorkflowApproval;
use Velm\Modules\Workflow\Models\WorkflowDefinition;
use Velm\Modules\Workflow\Models\WorkflowInstance;
use Velm\Modules\Workflow\Models\WorkflowTask;
use Velm\Modules\Workflow\WorkflowInstallHooks;
use Velm\Modules\Workflow\WorkflowSyncHooks;

return Manifest::make('workflow')
    ->version(0, 2, 0)
    ->depends('base', 'admin')
    ->models(
        WorkflowDefinition::class,
        WorkflowInstance::class,
        WorkflowApproval::class,
        WorkflowTask::class,
    )
    ->installHook(WorkflowInstallHooks::class)
    ->syncHook(WorkflowSyncHooks::class)
    ->data(
        'views/definition.php',
        'views/runtime.php',
        'views/menu.php',
    )
    ->summary('Approval workflows — state machines, stage forms, and multi-step sign-off on any model.')
    ->category('System')
    ->icon('heroicon-o-arrow-path');
