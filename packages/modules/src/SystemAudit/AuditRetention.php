<?php

declare(strict_types=1);

namespace Velm\Modules\SystemAudit;

use Velm\Environment;

final class AuditRetention
{
    public static function purge(Environment $env): int
    {
        return $env->withAclBypass(function () use ($env): int {
            $days = max(1, (int) config('velm.audit.retention_days', 90));
            $cutoff = gmdate('Y-m-d H:i:s', time() - ($days * 86400));
            $purged = 0;

            foreach (['ir.audit.log', 'ir.login.log', 'ir.user.lifecycle'] as $model) {
                if (! $env->registry->has($model)) {
                    continue;
                }

                $records = $env->model($model)->search([['created_at', '<', $cutoff]]);
                $count = $records->count();

                if ($count > 0) {
                    $records->unlink();
                }

                $purged += $count;
            }

            return $purged;
        });
    }
}
