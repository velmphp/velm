<?php

declare(strict_types=1);

use Velm\Modules\Manifest;
use Velm\Modules\SystemAudit\Models\AuditLog;
use Velm\Modules\SystemAudit\Models\LoginLog;
use Velm\Modules\SystemAudit\Models\UserLifecycle;
use Velm\Modules\SystemAudit\SystemAuditInstallHooks;

return Manifest::make('system_audit')
    ->version(0, 1, 0)
    ->depends('base', 'admin')
    ->models(AuditLog::class, LoginLog::class, UserLifecycle::class)
    ->installHook(SystemAuditInstallHooks::class)
    ->data(
        'views/audit_log.php',
        'views/login_log.php',
        'views/user_lifecycle.php',
        'views/menu.php',
    )
    ->summary('IT audit trail — system events, login history, and user lifecycle tracking.')
    ->category('Administration')
    ->icon('heroicon-o-shield-check');
