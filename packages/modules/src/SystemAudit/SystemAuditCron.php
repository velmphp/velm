<?php

declare(strict_types=1);

namespace Velm\Modules\SystemAudit;

use Velm\Environment;

final class SystemAuditCron
{
    public static function seedRetention(Environment $env): void
    {
        if (! $env->registry->has('ir.cron')) {
            return;
        }

        $cronName = 'Audit log retention';
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
                    'model' => 'ir.audit.log',
                    'action_type' => 'audit_purge',
                    'vals_json' => '{}',
                ]);
            }

            $actionId = $action->ids()[0];
        }

        $env->model('ir.cron')->create([
            'name' => $cronName,
            'action_id' => $actionId,
            'interval_number' => 1,
            'interval_type' => 'days',
            'active' => true,
        ]);
    }
}
