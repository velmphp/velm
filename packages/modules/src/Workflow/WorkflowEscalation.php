<?php

declare(strict_types=1);

namespace Velm\Modules\Workflow;

use Velm\Environment;

final class WorkflowEscalation
{
    public static function processOverdue(Environment $env): int
    {
        if (! $env->registry->has('workflow.approval')) {
            return 0;
        }

        $now = gmdate('Y-m-d H:i:s');
        $count = 0;

        foreach ($env->model('workflow.approval')->search([['status', '=', 'pending']])->read() as $appr) {
            $deadline = (string) ($appr['deadline_at'] ?? '');

            if ($deadline === '' || $deadline > $now) {
                continue;
            }

            if (self::escalateOne($env, $appr)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param  array<string, mixed>  $approvalRow
     */
    private static function escalateOne(Environment $env, array $approvalRow): bool
    {
        $instanceRow = WorkflowEngine::reloadInstance($env, (int) ($approvalRow['instance_id'] ?? 0));
        $defn = WorkflowEngine::definitionForInstance($env, $instanceRow);
        $tr = WorkflowEngine::transitionByKey($defn, (string) ($approvalRow['transition_key'] ?? ''));
        $cfg = is_array($tr['approval'] ?? null) ? $tr['approval'] : [];
        $escalateGid = (int) ($cfg['escalate_to_group_id'] ?? 0);

        if ($escalateGid <= 0) {
            return false;
        }

        $env->browse('workflow.approval', [(int) $approvalRow['id']])->write(['status' => 'cancelled']);

        $hours = (int) ($cfg['deadline_hours'] ?? 24);
        $env->model('workflow.approval')->create([
            'instance_id' => (int) $instanceRow['id'],
            'transition_key' => (string) ($approvalRow['transition_key'] ?? ''),
            'status' => 'pending',
            'requester_id' => $approvalRow['requester_id'] ?? null,
            'assignee_group_id' => $escalateGid,
            'sequence' => ((int) ($approvalRow['sequence'] ?? 0)) + 100,
            'form_data' => (string) ($approvalRow['form_data'] ?? '{}'),
            'deadline_at' => gmdate('Y-m-d H:i:s', time() + $hours * 3600),
        ]);

        return true;
    }
}
