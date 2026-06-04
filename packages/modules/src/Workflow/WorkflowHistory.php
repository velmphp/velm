<?php

declare(strict_types=1);

namespace Velm\Modules\Workflow;

use Velm\Environment;

final class WorkflowHistory
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function recordTimeline(
        Environment $env,
        string $resModel,
        int $resId,
        int $instanceId,
        string $definitionId = '',
    ): array {
        $defn = [];

        if ($definitionId !== '') {
            $rows = $env->browse('workflow.definition', [(int) $definitionId])->read(['definition']);
            $defn = WorkflowParser::parse((string) ($rows[0]['definition'] ?? '{}'));
        }

        $events = [];
        $pendingEvents = [];
        $approvalHistory = [];

        if ($env->registry->has('workflow.approval')) {
            try {
                $env->checkAccess('workflow.approval', 'read');
            } catch (\Throwable) {
                return [];
            }

            foreach ($env->model('workflow.approval')->search([
                ['instance_id', '=', $instanceId],
            ])->read() as $appr) {
                if (($appr['status'] ?? '') === 'pending') {
                    $pendingEvents[] = self::approvalEvent($appr, $defn);
                } elseif (($appr['acted_at'] ?? '') !== '' && ($appr['acted_at'] ?? null) !== null) {
                    $approvalHistory[] = self::approvalEvent($appr, $defn);
                }
            }
        }

        if ($events === [] && $approvalHistory !== []) {
            usort($approvalHistory, static fn (array $a, array $b): int => strcmp(
                (string) ($a['at'] ?? ''),
                (string) ($b['at'] ?? ''),
            ));
            $events = $approvalHistory;
        }

        return array_merge($events, $pendingEvents);
    }

    /**
     * @param  array<string, mixed>  $appr
     * @param  array<string, mixed>  $defn
     * @return array<string, mixed>
     */
    private static function approvalEvent(array $appr, array $defn): array
    {
        $trLabel = (string) ($appr['transition_key'] ?? '');

        foreach ($defn['transitions'] ?? [] as $tr) {
            if (is_array($tr) && ($tr['key'] ?? '') === $trLabel) {
                $trLabel = (string) ($tr['label'] ?? $trLabel);
                break;
            }
        }

        if (($appr['status'] ?? '') === 'pending') {
            return [
                'id' => 'appr-pending-'.($appr['id'] ?? 0),
                'kind' => 'pending',
                'variant' => 'warning',
                'title' => "Awaiting approval — {$trLabel}",
                'body' => '',
                'at_display' => 'Pending',
                'pending' => true,
            ];
        }

        $approved = ($appr['status'] ?? '') === 'approved';

        return [
            'id' => 'appr-'.($appr['id'] ?? 0),
            'kind' => $approved ? 'approved' : 'rejected',
            'variant' => $approved ? 'success' : 'danger',
            'title' => ($approved ? 'Approved' : 'Rejected')." — {$trLabel}",
            'body' => (string) ($appr['comment'] ?? ''),
            'at' => (string) ($appr['acted_at'] ?? ''),
            'at_display' => (string) ($appr['acted_at'] ?? ''),
            'pending' => false,
        ];
    }
}
