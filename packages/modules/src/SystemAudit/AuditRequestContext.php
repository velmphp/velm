<?php

declare(strict_types=1);

namespace Velm\Modules\SystemAudit;

final class AuditRequestContext
{
    /**
     * @return array{ip: string, user_agent: string, session_id: string}
     */
    public static function capture(): array
    {
        if (! function_exists('request')) {
            return [
                'ip' => '',
                'user_agent' => '',
                'session_id' => '',
            ];
        }

        $request = request();

        return [
            'ip' => (string) $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'session_id' => session()->getId(),
        ];
    }

    public static function sessionLifetimeMinutes(): int
    {
        if (! function_exists('config')) {
            return 120;
        }

        return max(1, (int) config('session.lifetime', 120));
    }
}
