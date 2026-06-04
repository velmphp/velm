<?php

declare(strict_types=1);

namespace Velm\Modules\Workflow;

use Velm\Environment;
use Velm\Exception\AccessDeniedException;

final class WorkflowService
{
    /**
     * @param  array<string, mixed>  $meta
     * @param  array<string, mixed>  $defn
     */
    public static function saveDefinition(
        Environment $env,
        int $definitionId,
        array $meta,
        array $defn,
    ): void {
        WorkflowSchema::validate($defn, $env->registry);
        $model = (string) ($meta['model'] ?? $defn['model'] ?? '');
        $active = (bool) ($meta['active'] ?? true);

        if ($active) {
            foreach ($env->model('workflow.definition')->search([
                ['model', '=', $model],
                ['active', '=', true],
            ])->read(['id']) as $row) {
                if ((int) $row['id'] !== $definitionId) {
                    $env->browse('workflow.definition', [(int) $row['id']])->write(['active' => false]);
                }
            }
        }

        $env->browse('workflow.definition', [$definitionId])->write([
            'name' => $meta['name'] ?? null,
            'description' => $meta['description'] ?? null,
            'model' => $model,
            'definition' => json_encode($defn, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT),
            'active' => $active,
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function formContext(Environment $env, string $resModel, int $resId): ?array
    {
        if (! $env->registry->has('workflow.instance')) {
            return null;
        }

        $readonly = false;

        try {
            $env->checkAccess('workflow.instance', 'read');
        } catch (AccessDeniedException) {
            $readonly = true;
        }

        $loadInstance = static fn () => WorkflowEngine::instanceForRecord($env, $resModel, $resId);
        $inst = $readonly ? $env->withAclBypass($loadInstance) : $loadInstance();

        if ($inst === null) {
            $loadDefn = static fn () => WorkflowEngine::activeDefinition($env, $resModel);
            $defnRec = $readonly ? $env->withAclBypass($loadDefn) : $loadDefn();

            if ($defnRec === null) {
                return null;
            }

            $defn = WorkflowParser::parse((string) ($defnRec['definition'] ?? '{}'));

            if (! empty($defn['auto_start']) && ! $readonly) {
                WorkflowRuntime::maybeAutoStart($env, $resModel, $resId);
                $inst = $readonly ? $env->withAclBypass($loadInstance) : $loadInstance();
            }

            if ($inst === null) {
                return [
                    'has_workflow' => true,
                    'started' => false,
                    'definition_name' => (string) ($defnRec['name'] ?? ''),
                    'can_start' => ! $readonly,
                    'auto_start' => ! empty($defn['auto_start']),
                    'statusbar' => self::statusbarFromDefn($defn, null),
                    'timeline' => [],
                    'readonly' => $readonly,
                ];
            }
        }

        $loadDefnInst = static fn () => WorkflowEngine::definitionForInstance($env, $inst);
        $defn = $readonly ? $env->withAclBypass($loadDefnInst) : $loadDefnInst();
        $stateLabel = (string) ($inst['state'] ?? '');

        foreach ($defn['states'] ?? [] as $st) {
            if (is_array($st) && ($st['key'] ?? '') === ($inst['state'] ?? '')) {
                $stateLabel = (string) ($st['label'] ?? $stateLabel);
                break;
            }
        }

        $pendingApprovals = [];

        if ($env->registry->has('workflow.approval') && $env->uid !== null) {
            foreach ($env->model('workflow.approval')->search([
                ['instance_id', '=', (int) $inst['id']],
                ['status', '=', 'pending'],
            ])->read() as $appr) {
                if (WorkflowEngine::userMayActOnApproval($env, $appr, $env->uid)) {
                    $pendingApprovals[] = self::approvalUi($appr, $defn);
                }
            }
        }

        $transitions = $readonly ? [] : WorkflowEngine::availableTransitions($env, $inst);

        return [
            'has_workflow' => true,
            'started' => true,
            'instance_id' => (int) $inst['id'],
            'state' => (string) ($inst['state'] ?? ''),
            'state_label' => $stateLabel,
            'pending_transition' => (string) ($inst['pending_transition'] ?? ''),
            'transitions' => $transitions,
            'pending_approvals' => $pendingApprovals,
            'can_start' => false,
            'auto_start' => ! empty($defn['auto_start']),
            'statusbar' => self::statusbarFromDefn($defn, (string) ($inst['state'] ?? '')),
            'timeline' => WorkflowHistory::recordTimeline(
                $env,
                $resModel,
                $resId,
                (int) $inst['id'],
                (string) ($inst['definition_id'] ?? ''),
            ),
            'readonly' => $readonly,
        ];
    }

    public static function startForRecord(Environment $env, string $resModel, int $resId): array
    {
        $env->checkAccess('workflow.instance', 'create');
        $inst = WorkflowEngine::start($env, $resModel, $resId);

        return WorkflowEngine::reloadInstance($env, $inst->ids()[0]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function inboxItems(Environment $env): array
    {
        return WorkflowInbox::listItems($env);
    }

    /**
     * @param  array<string, mixed>  $defn
     * @return list<array<string, mixed>>
     */
    public static function statusbarFromDefn(array $defn, ?string $currentKey): array
    {
        $statesList = is_array($defn['states'] ?? null) ? $defn['states'] : [];
        $currentIdx = -1;

        foreach ($statesList as $i => $st) {
            if (is_array($st) && ($st['key'] ?? '') === $currentKey) {
                $currentIdx = $i;
                break;
            }
        }

        $out = [];

        foreach ($statesList as $i => $st) {
            if (! is_array($st)) {
                continue;
            }

            $key = (string) ($st['key'] ?? '');
            $out[] = [
                'key' => $key,
                'label' => (string) ($st['label'] ?? $key),
                'current' => $key === $currentKey,
                'done' => $currentIdx >= 0 && $i < $currentIdx,
                'final' => ! empty($st['final']),
                'cancelled' => ! empty($st['cancelled']),
            ];
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $appr
     * @param  array<string, mixed>  $defn
     * @return array<string, mixed>
     */
    private static function approvalUi(array $appr, array $defn): array
    {
        $trLabel = (string) ($appr['transition_key'] ?? '');

        foreach ($defn['transitions'] ?? [] as $tr) {
            if (is_array($tr) && ($tr['key'] ?? '') === $trLabel) {
                $trLabel = (string) ($tr['label'] ?? $trLabel);
                break;
            }
        }

        return [
            'id' => (int) ($appr['id'] ?? 0),
            'transition_key' => (string) ($appr['transition_key'] ?? ''),
            'transition_label' => $trLabel,
        ];
    }
}
