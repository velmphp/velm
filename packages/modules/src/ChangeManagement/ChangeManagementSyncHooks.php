<?php

declare(strict_types=1);

namespace Velm\Modules\ChangeManagement;

use Velm\Environment;
use Velm\Modules\Workflow\WorkflowDefinitions;
use Velm\Modules\Workflow\WorkflowRuntime;

final class ChangeManagementSyncHooks
{
    public static function sync(Environment $env): void
    {
        WorkflowDefinitions::seedChangeManagement($env);
        WorkflowRuntime::backfillAutoStart($env, 'it.change');
    }
}
