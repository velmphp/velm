<?php

declare(strict_types=1);

namespace Velm\Modules\Workflow;

use Velm\Environment;

final class WorkflowInbox
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function listItems(Environment $env): array
    {
        if (! $env->registry->has('workflow.approval') || $env->uid === null) {
            return [];
        }

        $out = [];

        foreach ($env->model('workflow.approval')->search(
            [['status', '=', 'pending']],
            order: '"id" DESC',
        )->read() as $appr) {
            if (! WorkflowEngine::userMayActOnApproval($env, $appr, $env->uid)) {
                continue;
            }

            $inst = WorkflowEngine::reloadInstance($env, (int) ($appr['instance_id'] ?? 0));
            $defn = WorkflowEngine::definitionForInstance($env, $inst);
            $trLabel = (string) ($appr['transition_key'] ?? '');

            foreach ($defn['transitions'] ?? [] as $tr) {
                if (is_array($tr) && ($tr['key'] ?? '') === $trLabel) {
                    $trLabel = (string) ($tr['label'] ?? $trLabel);
                    break;
                }
            }

            $stateLabel = (string) ($inst['state'] ?? '');

            foreach ($defn['states'] ?? [] as $st) {
                if (is_array($st) && ($st['key'] ?? '') === ($inst['state'] ?? '')) {
                    $stateLabel = (string) ($st['label'] ?? $stateLabel);
                    break;
                }
            }

            $recordLabel = ($inst['res_model'] ?? '').' #'.($inst['res_id'] ?? '');
            $rows = $env->browse((string) $inst['res_model'], [(int) $inst['res_id']])->read(['name']);

            if (($rows[0]['name'] ?? '') !== '') {
                $recordLabel = (string) $rows[0]['name'];
            }

            $out[] = [
                'id' => (int) ($appr['id'] ?? 0),
                'transition_label' => $trLabel,
                'state_label' => $stateLabel,
                'record_label' => $recordLabel,
                'res_model' => (string) ($inst['res_model'] ?? ''),
                'res_id' => (int) ($inst['res_id'] ?? 0),
                'deadline_at' => (string) ($appr['deadline_at'] ?? ''),
                'form_href' => self::recordFormHref((string) $inst['res_model'], (int) $inst['res_id']),
            ];
        }

        return $out;
    }

    private static function recordFormHref(string $resModel, int $resId): ?string
    {
        return match ($resModel) {
            'it.change' => "/velm/views/change_management/change.detail/{$resId}",
            'res.partner' => "/velm/views/partners/partner.detail/{$resId}",
            default => null,
        };
    }
}
