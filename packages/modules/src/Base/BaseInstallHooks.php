<?php

declare(strict_types=1);

namespace Velm\Modules\Base;

use Velm\Environment;

final class BaseInstallHooks
{
    public static function install(Environment $env): void
    {
        if ($env->model('res.groups')->search()->count() > 0) {
            return;
        }

        $adminGroup = $env->model('res.groups')->create(['name' => 'Admin']);
        $env->model('res.groups')->create(['name' => 'User']);
        $env->model('res.groups')->create(['name' => 'Public']);

        $company = null;

        if ($env->model('res.company')->search()->count() === 0) {
            $company = $env->model('res.company')->create(['name' => 'My Company']);
        } else {
            $company = $env->model('res.company')->search(limit: 1);
        }

        $userValues = [
            'name' => 'Administrator',
            'login' => 'admin',
            'password' => 'admin',
            'group_ids' => $adminGroup->ids(),
        ];

        if ($company->count() > 0) {
            $userValues['company_id'] = $company->ids()[0];
        }

        $env->model('res.users')->create($userValues);

        $access = $env->model('ir.model.access');

        foreach ([
            'res.company',
            'res.groups',
            'res.users',
            'ir.model.access',
            'ir.rule',
            'ir.ui.view',
            'ir.ui.menu',
            'ir.actions.server',
            'ir.cron',
        ] as $model) {
            if (! $env->registry->has($model)) {
                continue;
            }

            $access->create([
                'name' => "Admin/{$model}",
                'model' => $model,
                'group_id' => $adminGroup->ids()[0],
                'perm_read' => true,
                'perm_write' => true,
                'perm_create' => true,
                'perm_unlink' => true,
            ]);
        }

        $access->create([
            'name' => 'Authenticated/res.users (self)',
            'model' => 'res.users',
            'group_id' => null,
            'perm_read' => true,
            'perm_write' => false,
            'perm_create' => false,
            'perm_unlink' => false,
        ]);

        $access->create([
            'name' => 'Authenticated/ir.ui.view (read)',
            'model' => 'ir.ui.view',
            'group_id' => null,
            'perm_read' => true,
            'perm_write' => false,
            'perm_create' => false,
            'perm_unlink' => false,
        ]);
    }
}
