<?php

declare(strict_types=1);

use Velm\Modules\ChangeManagement\ChangeManagementInstallHooks;
use Velm\Modules\ChangeManagement\ChangeManagementSyncHooks;
use Velm\Modules\ChangeManagement\Models\Change;
use Velm\Modules\Manifest;

return Manifest::make('change_management')
    ->version(0, 1, 0)
    ->depends('base', 'admin', 'mail', 'workflow')
    ->models(Change::class)
    ->installHook(ChangeManagementInstallHooks::class)
    ->syncHook(ChangeManagementSyncHooks::class)
    ->data(
        'views/change.php',
        'views/menu.php',
    )
    ->summary('ICT change management — RFC, risk review, CAB approval, implementation, and PIR.')
    ->category('Operations')
    ->icon('heroicon-o-wrench-screwdriver');
