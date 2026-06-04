<?php

declare(strict_types=1);

namespace Velm\Modules\Workflow;

use Velm\Environment;

final class WorkflowInstallHooks
{
    public static function install(Environment $env): void
    {
        WorkflowAccess::install($env);
        WorkflowDefinitions::seedPartnerDemo($env);
        WorkflowCron::seedEscalation($env);
    }
}
