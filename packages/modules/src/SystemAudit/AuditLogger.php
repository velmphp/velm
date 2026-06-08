<?php

declare(strict_types=1);

namespace Velm\Modules\SystemAudit;

use Velm\Environment;

final class AuditLogger
{
    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    public static function log(
        Environment $env,
        string $model,
        int $resId,
        string $action,
        array $oldValues = [],
        array $newValues = [],
        ?string $summary = null,
    ): void {
        if (! $env->registry->has('ir.audit.log')) {
            return;
        }

        $context = AuditRequestContext::capture();
        $name = $summary ?? self::summary($model, $resId, $action);
        $companyId = self::resolveCompanyId($env, $model, $resId, $oldValues, $newValues);
        $auditEnv = self::auditEnvironment($env, $companyId);

        $auditEnv->withAclBypass(function () use ($auditEnv, $model, $resId, $action, $oldValues, $newValues, $context, $name, $companyId): void {
            $auditEnv->model('ir.audit.log')->create([
                'name' => $name,
                'model' => $model,
                'res_id' => $resId,
                'action' => $action,
                'user_id' => $auditEnv->uid,
                'company_id' => $companyId,
                'old_values' => $oldValues !== [] ? json_encode($oldValues, JSON_THROW_ON_ERROR) : null,
                'new_values' => $newValues !== [] ? json_encode($newValues, JSON_THROW_ON_ERROR) : null,
                'ip_address' => $context['ip'] !== '' ? $context['ip'] : null,
                'user_agent' => $context['user_agent'] !== '' ? $context['user_agent'] : null,
            ]);
        });
    }

    private static function summary(string $model, int $resId, string $action): string
    {
        return "{$action} {$model}#{$resId}";
    }

    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    private static function resolveCompanyId(
        Environment $env,
        string $model,
        int $resId,
        array $oldValues,
        array $newValues,
    ): ?int {
        if ($model === 'res.company' && $resId > 0) {
            return $resId;
        }

        foreach ([$newValues['company_id'] ?? null, $oldValues['company_id'] ?? null] as $candidate) {
            if ($candidate !== null && $candidate !== '' && $candidate !== false) {
                return (int) $candidate;
            }
        }

        return $env->companyId();
    }

    private static function auditEnvironment(Environment $env, ?int $companyId): Environment
    {
        if ($companyId === null) {
            return $env;
        }

        return $env->withContext(['company_id' => $companyId]);
    }
}
