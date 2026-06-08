<?php

declare(strict_types=1);

namespace Velm\Modules\SystemAudit;

use Velm\Environment;

final class SystemAuditInstallHooks
{
    public static function install(Environment $env): void
    {
        self::grantAccess($env);
        SystemAuditCron::seedRetention($env);
    }

    private static function grantAccess(Environment $env): void
    {
        if (! $env->registry->has('ir.model.access')) {
            return;
        }

        $access = $env->model('ir.model.access');

        $admin = $env->model('res.groups')->search([['name', '=', 'Admin']], limit: 1);

        if ($admin->count() === 0) {
            return;
        }

        $adminId = $admin->ids()[0];

        foreach (['ir.audit.log', 'ir.login.log', 'ir.user.lifecycle'] as $model) {
            self::grant($access, $adminId, $model, read: true, write: false, create: false, unlink: false);
        }

        $user = $env->model('res.groups')->search([['name', '=', 'User']], limit: 1);

        if ($user->count() > 0) {
            self::grant($access, $user->ids()[0], 'ir.login.log', read: true, write: false, create: false, unlink: false);
        }
    }

    /**
     * @param  \Velm\Recordset\Recordset  $access
     */
    private static function grant(
        $access,
        int $groupId,
        string $model,
        bool $read,
        bool $write,
        bool $create,
        bool $unlink,
    ): void {
        $existing = $access->search([
            ['model', '=', $model],
            ['group_id', '=', $groupId],
        ], limit: 1);

        $vals = [
            'perm_read' => $read,
            'perm_write' => $write,
            'perm_create' => $create,
            'perm_unlink' => $unlink,
        ];

        if ($existing->count() > 0) {
            $existing->write($vals);

            return;
        }

        $access->create([
            'name' => "system_audit/{$model}/group-{$groupId}",
            'model' => $model,
            'group_id' => $groupId,
            ...$vals,
        ]);
    }
}
