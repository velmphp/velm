<?php

declare(strict_types=1);

use Addons\ChangeManagement\ChangeManagementInstallHooks;
use Addons\ChangeManagement\ChangeManagementSyncHooks;
use Velm\Modules\Manifest;

return Manifest::make('change_management')
    ->version(0, 1, 0)
    ->depends('base', 'admin', 'mail', 'workflow')
    ->installHook(ChangeManagementInstallHooks::class)
    ->syncHook(ChangeManagementSyncHooks::class)
    ->data(
        'views/change.php',
        'views/menu.php',
    )
    ->summary('Demo — ICT change requests with workflow, rich text, and activity.')
    ->category('Demos')
    ->icon('heroicon-o-wrench-screwdriver');
