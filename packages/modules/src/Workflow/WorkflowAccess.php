<?php

declare(strict_types=1);

namespace Velm\Modules\Workflow;

use Velm\Environment;

final class WorkflowAccess
{
    public static function install(Environment $env): void
    {
        if (! $env->registry->has('ir.model.access')) {
            return;
        }

        $admin = $env->model('res.groups')->search([['name', '=', 'Admin']], limit: 1);

        if ($admin->count() === 0) {
            return;
        }

        $adminId = $admin->ids()[0];
        $access = $env->model('ir.model.access');

        self::grant($access, $adminId, 'workflow.definition', write: true);
        self::grant($access, $adminId, 'workflow.instance', write: true);
        self::grant($access, $adminId, 'workflow.approval', write: true);
        self::grant($access, $adminId, 'workflow.task', write: true);

        $user = $env->model('res.groups')->search([['name', '=', 'User']], limit: 1);

        if ($user->count() > 0) {
            $userId = $user->ids()[0];
            self::grant($access, $userId, 'workflow.approval', write: false);
        }

        foreach (['Change Manager', 'Change Advisory Board'] as $groupName) {
            $grp = $env->model('res.groups')->search([['name', '=', $groupName]], limit: 1);

            if ($grp->count() === 0) {
                $grp = $env->model('res.groups')->create(['name' => $groupName]);
            }

            $gid = $grp->ids()[0];
            self::grant($access, $gid, 'workflow.approval', write: true);
            self::grant($access, $gid, 'workflow.instance', write: false);
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
            'name' => "workflow/{$model}/group-{$groupId}",
            'model' => $model,
            'group_id' => $groupId,
            ...$vals,
        ]);
    }
}
