<?php

declare(strict_types=1);

namespace Velm\Modules\SystemAudit;

use Velm\Environment;

final class AuditLoginLogger
{
    public static function log(
        Environment $env,
        string $event,
        ?int $userId = null,
        ?string $email = null,
    ): void {
        if (! $env->registry->has('ir.login.log')) {
            return;
        }

        $context = AuditRequestContext::capture();

        $env->withAclBypass(function () use ($env, $event, $userId, $email, $context): void {
            $env->model('ir.login.log')->create([
                'user_id' => $userId,
                'email' => $email,
                'event' => $event,
                'ip_address' => $context['ip'] !== '' ? $context['ip'] : null,
                'user_agent' => $context['user_agent'] !== '' ? $context['user_agent'] : null,
                'session_id' => $context['session_id'] !== '' ? $context['session_id'] : null,
                'session_lifetime_minutes' => AuditRequestContext::sessionLifetimeMinutes(),
            ]);
        });
    }
}
