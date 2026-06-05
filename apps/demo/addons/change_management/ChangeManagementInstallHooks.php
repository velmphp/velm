<?php

declare(strict_types=1);

namespace Addons\ChangeManagement;

use Velm\Environment;
use Velm\Modules\Workflow\WorkflowDefinitions;
use Velm\Modules\Workflow\WorkflowRuntime;

final class ChangeManagementInstallHooks
{
    public static function install(Environment $env): void
    {
        self::grantAccess($env);
        WorkflowDefinitions::seedChangeManagement($env);
        WorkflowRuntime::backfillAutoStart($env, 'it.change');
    }

    private static function grantAccess(Environment $env): void
    {
        if (! $env->registry->has('ir.model.access')) {
            return;
        }

        $admin = $env->model('res.groups')->search([['name', '=', 'Admin']], limit: 1);

        if ($admin->count() === 0) {
            return;
        }

        $access = $env->model('ir.model.access');
        $adminId = $admin->ids()[0];

        self::grant($access, $adminId, 'it.change', write: true);

        $user = $env->model('res.groups')->search([['name', '=', 'User']], limit: 1);

        if ($user->count() > 0) {
            self::grant($access, $user->ids()[0], 'it.change', write: true);
        }

        foreach (['Change Manager', 'Change Advisory Board'] as $groupName) {
            $grp = $env->model('res.groups')->search([['name', '=', $groupName]], limit: 1);

            if ($grp->count() === 0) {
                continue;
            }

            self::grant($access, $grp->ids()[0], 'it.change', write: true);
        }
    }

    /**
     * @param  \Velm\Recordset\Recordset  $access
     */
    private static function grant($access, int $groupId, string $model, bool $write): void
    {
        $existing = $access->search([
            ['model', '=', $model],
            ['group_id', '=', $groupId],
        ], limit: 1);

        $vals = [
            'perm_read' => true,
            'perm_write' => $write,
            'perm_create' => $write,
            'perm_unlink' => $write,
        ];

        if ($existing->count() > 0) {
            $existing->write($vals);

            return;
        }

        $access->create([
            'name' => "change_management/{$model}/group-{$groupId}",
            'model' => $model,
            'group_id' => $groupId,
            ...$vals,
        ]);
    }
}
