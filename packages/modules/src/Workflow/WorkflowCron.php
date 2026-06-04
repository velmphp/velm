<?php

declare(strict_types=1);

namespace Velm\Modules\Workflow;

use Velm\Environment;

final class WorkflowCron
{
    public static function seedEscalation(Environment $env): void
    {
        if (! $env->registry->has('ir.cron')) {
            return;
        }

        $cronName = 'Workflow approval escalation';
        $existing = $env->model('ir.cron')->search([['name', '=', $cronName]], limit: 1);

        if ($existing->count() > 0) {
            return;
        }

        $actionId = null;

        if ($env->registry->has('ir.actions.server')) {
            $action = $env->model('ir.actions.server')->search([['name', '=', $cronName]], limit: 1);

            if ($action->count() === 0) {
                $action = $env->model('ir.actions.server')->create([
                    'name' => $cronName,
                    'model' => 'workflow.approval',
                    'action_type' => 'workflow_escalate',
                    'vals_json' => '{}',
                ]);
            }

            $actionId = $action->ids()[0];
        }

        $env->model('ir.cron')->create([
            'name' => $cronName,
            'action_id' => $actionId,
            'interval_number' => 15,
            'interval_type' => 'minutes',
            'active' => true,
        ]);
    }
}
