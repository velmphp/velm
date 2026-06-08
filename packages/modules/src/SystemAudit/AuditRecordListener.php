<?php

declare(strict_types=1);

namespace Velm\Modules\SystemAudit;

use Velm\Environment;
use Velm\Recordset\Recordset;
use Velm\Support\RecordChangeNotifier;

final class AuditRecordListener
{
    public static function register(): void
    {
        RecordChangeNotifier::listen(static function (
            Environment $env,
            Recordset $recordset,
            array $values,
            string $operation,
            array $context,
        ): void {
            self::handle($env, $recordset, $values, $operation, $context);
        });
    }

    /**
     * @param  array<string, mixed>  $values
     * @param  array<string, mixed>  $context
     */
    private static function handle(
        Environment $env,
        Recordset $recordset,
        array $values,
        string $operation,
        array $context,
    ): void {
        if (! $env->registry->has('ir.audit.log')) {
            return;
        }

        $model = $recordset->modelName();

        if (self::isAuditModel($model)) {
            return;
        }

        /** @var array<int, array<string, mixed>> $snapshots */
        $snapshots = is_array($context['snapshots'] ?? null) ? $context['snapshots'] : [];

        if ($model === 'res.users') {
            self::trackUserLifecycle($env, $recordset, $values, $operation, $snapshots);
        }

        foreach ($recordset->ids() as $id) {
            $oldValues = $snapshots[$id] ?? [];
            $newValues = $values;

            if ($operation === 'unlink') {
                $newValues = [];
            }

            AuditLogger::log($env, $model, $id, $operation, $oldValues, $newValues);
        }
    }

    /**
     * @param  array<string, mixed>  $values
     * @param  array<int, array<string, mixed>>  $snapshots
     */
    private static function trackUserLifecycle(
        Environment $env,
        Recordset $recordset,
        array $values,
        string $operation,
        array $snapshots,
    ): void {
        if (! $env->registry->has('ir.user.lifecycle')) {
            return;
        }

        foreach ($recordset->ids() as $id) {
            match ($operation) {
                'create' => AuditUserLifecycle::trackCreate($env, $id, $values),
                'write' => AuditUserLifecycle::trackWrite($env, $id, $values, $snapshots[$id] ?? []),
                'unlink' => AuditUserLifecycle::trackDelete($env, $id),
                default => null,
            };
        }
    }

    private static function isAuditModel(string $model): bool
    {
        return in_array($model, ['ir.audit.log', 'ir.login.log', 'ir.user.lifecycle'], true);
    }
}
