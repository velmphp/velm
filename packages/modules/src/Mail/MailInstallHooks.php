<?php

declare(strict_types=1);

namespace Velm\Modules\Mail;

use Velm\Environment;

final class MailInstallHooks
{
    public static function install(Environment $env): void
    {
        self::grantAccess($env);
    }

    private static function grantAccess(Environment $env): void
    {
        if (! $env->registry->has('ir.model.access')) {
            return;
        }

        $access = $env->model('ir.model.access');

        foreach (['Admin', 'User'] as $groupName) {
            $group = $env->model('res.groups')->search([['name', '=', $groupName]], limit: 1);

            if ($group->count() === 0) {
                continue;
            }

            $groupId = $group->ids()[0];

            foreach (['mail.message', 'mail.follower'] as $model) {
                self::grant($access, $groupId, $model);
            }
        }
    }

    /**
     * @param  \Velm\Recordset\Recordset  $access
     */
    private static function grant($access, int $groupId, string $model): void
    {
        $existing = $access->search([
            ['model', '=', $model],
            ['group_id', '=', $groupId],
        ], limit: 1);

        $vals = [
            'perm_read' => true,
            'perm_write' => true,
            'perm_create' => true,
            'perm_unlink' => true,
        ];

        if ($existing->count() > 0) {
            $existing->write($vals);

            return;
        }

        $access->create([
            'name' => "mail/{$model}/group-{$groupId}",
            'model' => $model,
            'group_id' => $groupId,
            ...$vals,
        ]);
    }
}
