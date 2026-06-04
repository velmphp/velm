<?php

declare(strict_types=1);

namespace Velm\Modules\Workflow;

use Velm\Environment;

final class WorkflowRuntime
{
    public static function maybeAutoStart(Environment $env, string $resModel, int $resId): void
    {
        if (! $env->registry->has('workflow.definition') || ! $env->registry->has('workflow.instance')) {
            return;
        }

        try {
            $defnRec = WorkflowEngine::activeDefinition($env, $resModel);

            if ($defnRec === null) {
                return;
            }

            $defn = WorkflowParser::parse((string) ($defnRec['definition'] ?? '{}'));

            if (empty($defn['auto_start'])) {
                return;
            }

            if (WorkflowEngine::instanceForRecord($env, $resModel, $resId) !== null) {
                return;
            }

            WorkflowEngine::start($env, $resModel, $resId, $defnRec);
        } catch (\Throwable) {
            // Auto-start must not break the caller transaction.
        }
    }

    public static function backfillAutoStart(Environment $env, string $modelName): int
    {
        $defnRec = WorkflowEngine::activeDefinition($env, $modelName);

        if ($defnRec === null) {
            return 0;
        }

        $defn = WorkflowParser::parse((string) ($defnRec['definition'] ?? '{}'));

        if (empty($defn['auto_start'])) {
            return 0;
        }

        $count = 0;

        foreach ($env->model($modelName)->search()->read(['id']) as $row) {
            $id = (int) ($row['id'] ?? 0);

            if ($id <= 0) {
                continue;
            }

            if (WorkflowEngine::instanceForRecord($env, $modelName, $id) !== null) {
                continue;
            }

            self::maybeAutoStart($env, $modelName, $id);

            if (WorkflowEngine::instanceForRecord($env, $modelName, $id) !== null) {
                $count++;
            }
        }

        return $count;
    }
}
