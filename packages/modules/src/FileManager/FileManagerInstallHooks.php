<?php

declare(strict_types=1);

namespace Velm\Modules\FileManager;

use Velm\Environment;

final class FileManagerInstallHooks
{
    public static function install(Environment $env): void
    {
        FileManagerCompanyScope::backfillOrphans($env);

        $admin = $env->model('res.groups')->search([['name', '=', 'Admin']], limit: 1);

        if ($admin->count() === 0) {
            return;
        }

        $user = $env->model('res.groups')->search([['name', '=', 'User']], limit: 1);
        $access = $env->model('ir.model.access');

        self::grant($env, $access, $admin->ids()[0], 'ir.attachment', write: true);
        self::grant($env, $access, $admin->ids()[0], 'res.attachment.folder', write: true);

        if ($user->count() > 0) {
            self::grant($env, $access, $user->ids()[0], 'ir.attachment', write: false);
            self::grant($env, $access, $user->ids()[0], 'res.attachment.folder', write: false);
        }
    }

    /**
     * @param  \Velm\Recordset\Recordset  $access
     */
    private static function grant(
        Environment $env,
        $access,
        int $groupId,
        string $model,
        bool $write,
    ): void {
        if (! $env->registry->has($model)) {
            return;
        }

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
            'name' => "file_manager/{$model}/group-{$groupId}",
            'model' => $model,
            'group_id' => $groupId,
            ...$vals,
        ]);
    }
}
