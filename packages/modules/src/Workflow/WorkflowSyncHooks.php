<?php

declare(strict_types=1);

namespace Velm\Modules\Workflow;

use Velm\Environment;

final class WorkflowSyncHooks
{
    public static function sync(Environment $env): void
    {
        WorkflowAccess::install($env);
        WorkflowDefinitions::seedPartnerDemo($env);

        if ($env->registry->has('it.change')) {
            WorkflowDefinitions::seedChangeManagement($env);
            WorkflowRuntime::backfillAutoStart($env, 'it.change');
        }

        WorkflowCron::seedEscalation($env);
    }
}
