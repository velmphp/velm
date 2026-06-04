<?php

declare(strict_types=1);

namespace Velm\Modules\Workflow;

use Velm\Environment;
use Velm\Exception\AccessDeniedException;
use Velm\Recordset\Recordset;

final class WorkflowEngine
{
    /**
     * @return array<string, mixed>|null
     */
    public static function activeDefinition(Environment $env, string $modelName): ?array
    {
        if (! $env->registry->has('workflow.definition')) {
            return null;
        }

        $rows = $env->model('workflow.definition')->search([
            ['model', '=', $modelName],
            ['active', '=', true],
        ], limit: 1, order: '"id" DESC')->read(['id', 'name', 'definition']);

        return $rows[0] ?? null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function instanceForRecord(Environment $env, string $resModel, int $resId): ?array
    {
        if (! $env->registry->has('workflow.instance')) {
            return null;
        }

        $rows = $env->model('workflow.instance')->search([
            ['res_model', '=', $resModel],
            ['res_id', '=', $resId],
            ['active', '=', true],
        ], limit: 1)->read();

        return $rows[0] ?? null;
    }

    /**
     * @param  array<string, mixed>|null  $definitionRow
     * @return Recordset
     */
    public static function start(
        Environment $env,
        string $modelName,
        int $recordId,
        ?array $definitionRow = null,
    ): Recordset {
        $definitionRow ??= self::activeDefinition($env, $modelName);

        if ($definitionRow === null) {
            throw new WorkflowDefinitionError("No active workflow for {$modelName}");
        }

        $defn = WorkflowParser::parse((string) ($definitionRow['definition'] ?? '{}'));
        WorkflowSchema::validate($defn, $env->registry);

        if (($defn['model'] ?? '') !== $modelName) {
            throw new WorkflowDefinitionError('Workflow model mismatch');
        }

        $existing = self::instanceForRecord($env, $modelName, $recordId);

        if ($existing !== null) {
            return $env->browse('workflow.instance', [(int) $existing['id']]);
        }

        $initial = self::initialState($defn);

        return $env->model('workflow.instance')->create([
            'definition_id' => (int) $definitionRow['id'],
            'res_model' => $modelName,
            'res_id' => $recordId,
            'state' => $initial,
            'stage_data' => '{}',
            'started_by' => $env->uid,
            'active' => true,
            'state_updated_at' => gmdate('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @param  array<string, mixed>  $instanceRow
     * @return list<array<string, mixed>>
     */
    public static function availableTransitions(
        Environment $env,
        array $instanceRow,
        ?int $userId = null,
    ): array {
        if (($instanceRow['pending_transition'] ?? '') !== '' && ($instanceRow['pending_transition'] ?? null) !== null) {
            return [];
        }

        $defn = self::definitionForInstance($env, $instanceRow);
        $uid = $userId ?? $env->uid;
        $out = [];

        foreach ($defn['transitions'] ?? [] as $tr) {
            if (! is_array($tr)) {
                continue;
            }

            if (! in_array($instanceRow['state'] ?? '', $tr['from'] ?? [], true)) {
                continue;
            }

            if (! self::userMayTrigger($tr, $uid)) {
                continue;
            }

            $out[] = self::transitionUi($tr);
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $instanceRow
     * @param  array<string, mixed>  $formValues
     * @return array<string, mixed>
     */
    public static function applyTransition(
        Environment $env,
        array $instanceRow,
        string $transitionKey,
        array $formValues = [],
        ?int $userId = null,
    ): array {
        if (($instanceRow['pending_transition'] ?? '') !== '' && ($instanceRow['pending_transition'] ?? null) !== null) {
            throw new WorkflowDefinitionError('A transition is already awaiting approval');
        }

        $defn = self::definitionForInstance($env, $instanceRow);
        $tr = self::transitionByKey($defn, $transitionKey);

        if (! in_array($instanceRow['state'] ?? '', $tr['from'] ?? [], true)) {
            throw new WorkflowDefinitionError(
                "Transition {$transitionKey} not allowed from state ".(string) ($instanceRow['state'] ?? ''),
            );
        }

        $uid = $userId ?? $env->uid;

        if (! self::userMayTrigger($tr, $uid)) {
            throw new AccessDeniedException('You cannot run this transition');
        }

        self::validateForm($tr, $formValues);
        [$stagePatch, $recordPatch] = self::splitFormValues($tr, $formValues);
        $stageData = WorkflowParser::loadJson($instanceRow['stage_data'] ?? '{}');
        $stageData = array_merge($stageData, $stagePatch);

        if ($recordPatch !== []) {
            $env->browse($instanceRow['res_model'], [(int) $instanceRow['res_id']])->write($recordPatch);
        }

        $kind = $tr['kind'] ?? 'user';
        $instanceId = (int) $instanceRow['id'];

        if ($kind === 'approval') {
            self::startApproval($env, $instanceRow, $tr, $stageData, (int) ($uid ?? 0));
            $env->browse('workflow.instance', [$instanceId])->write([
                'stage_data' => json_encode($stageData, JSON_THROW_ON_ERROR),
                'pending_transition' => $transitionKey,
            ]);

            return self::reloadInstance($env, $instanceId);
        }

        $env->browse('workflow.instance', [$instanceId])->write([
            'state' => $tr['to'],
            'stage_data' => json_encode($stageData, JSON_THROW_ON_ERROR),
            'pending_transition' => null,
            'state_updated_at' => gmdate('Y-m-d H:i:s'),
        ]);

        return self::reloadInstance($env, $instanceId);
    }

    /**
     * @param  array<string, mixed>  $approvalRow
     */
    public static function approve(
        Environment $env,
        array $approvalRow,
        bool $approved = true,
        string $comment = '',
        ?int $userId = null,
    ): array {
        if (($approvalRow['status'] ?? '') !== 'pending') {
            throw new WorkflowDefinitionError('Approval is not pending');
        }

        $uid = $userId ?? $env->uid;

        if (! self::userMayActOnApproval($env, $approvalRow, $uid)) {
            throw new AccessDeniedException('You cannot act on this approval');
        }

        $instanceRow = self::reloadInstance(
            $env,
            (int) ($approvalRow['instance_id'] ?? 0),
        );
        $defn = self::definitionForInstance($env, $instanceRow);
        $tr = self::transitionByKey($defn, (string) ($approvalRow['transition_key'] ?? ''));

        $env->browse('workflow.approval', [(int) $approvalRow['id']])->write([
            'status' => $approved ? 'approved' : 'rejected',
            'acted_by' => $uid,
            'acted_at' => gmdate('Y-m-d H:i:s'),
            'comment' => $comment !== '' ? $comment : null,
        ]);

        if ($env->registry->has('workflow.task') && $uid !== null) {
            $tasks = $env->model('workflow.task')->search([
                ['instance_id', '=', (int) $instanceRow['id']],
                ['user_id', '=', $uid],
                ['state', '=', 'open'],
            ]);

            if ($tasks->count() > 0) {
                $tasks->write(['state' => $approved ? 'done' : 'cancelled']);
            }
        }

        $instanceId = (int) $instanceRow['id'];

        if (! $approved) {
            $rejectTo = $tr['reject_to'] ?? ($tr['from'][0] ?? 'draft');
            $env->browse('workflow.instance', [$instanceId])->write([
                'state' => $rejectTo,
                'pending_transition' => null,
                'state_updated_at' => gmdate('Y-m-d H:i:s'),
            ]);

            return self::reloadInstance($env, $instanceId);
        }

        if (! self::approvalsComplete($env, $instanceRow, $tr)) {
            self::maybeAdvanceSequential($env, $instanceRow, $tr);

            return self::reloadInstance($env, $instanceId);
        }

        $env->browse('workflow.instance', [$instanceId])->write([
            'state' => $tr['to'],
            'pending_transition' => null,
            'state_updated_at' => gmdate('Y-m-d H:i:s'),
        ]);

        return self::reloadInstance($env, $instanceId);
    }

    /**
     * @param  array<string, mixed>  $defn
     */
    public static function initialState(array $defn): string
    {
        foreach ($defn['states'] ?? [] as $st) {
            if (is_array($st) && ! empty($st['initial'])) {
                return (string) $st['key'];
            }
        }

        throw new WorkflowDefinitionError('No initial state');
    }

    /**
     * @param  array<string, mixed>  $defn
     * @return array<string, mixed>
     */
    public static function transitionByKey(array $defn, string $key): array
    {
        foreach ($defn['transitions'] ?? [] as $tr) {
            if (is_array($tr) && ($tr['key'] ?? '') === $key) {
                return $tr;
            }
        }

        throw new WorkflowDefinitionError("Unknown transition {$key}");
    }

    /**
     * @param  array<string, mixed>  $tr
     * @return array<string, mixed>
     */
    public static function transitionUi(array $tr): array
    {
        $form = is_array($tr['form'] ?? null) ? $tr['form'] : [];

        return [
            'key' => $tr['key'],
            'label' => $tr['label'] ?? $tr['key'],
            'kind' => $tr['kind'] ?? 'user',
            'form_title' => $form['title'] ?? ($tr['label'] ?? $tr['key']),
            'form_fields' => $form['fields'] ?? [],
        ];
    }

    /**
     * @param  array<string, mixed>  $tr
     */
    public static function userMayTrigger(array $tr, ?int $uid): bool
    {
        if ($uid === Environment::SUPERUSER_ID) {
            return true;
        }

        return ($tr['kind'] ?? 'user') !== 'automatic';
    }

    /**
     * @param  array<string, mixed>  $approvalRow
     */
    public static function userMayActOnApproval(Environment $env, array $approvalRow, ?int $uid): bool
    {
        if ($uid === Environment::SUPERUSER_ID) {
            return true;
        }

        $assigneeUser = (int) ($approvalRow['assignee_user_id'] ?? 0);

        if ($assigneeUser > 0 && $assigneeUser === $uid) {
            return true;
        }

        $groupId = (int) ($approvalRow['assignee_group_id'] ?? 0);

        if ($groupId > 0 && $uid !== null) {
            return in_array($groupId, $env->userGroupIds(), true);
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $instanceRow
     * @param  array<string, mixed>  $tr
     * @return list<array{user_id?: int, group_id?: int}>
     */
    public static function resolveAssignees(
        Environment $env,
        array $instanceRow,
        array $tr,
        int $requesterId,
    ): array {
        $approval = is_array($tr['approval'] ?? null) ? $tr['approval'] : [];
        $assigneeType = $approval['assignee_type'] ?? 'group';
        $strategy = $approval['strategy'] ?? 'any';

        if ($assigneeType === 'user') {
            $userId = (int) ($approval['user_id'] ?? 0);

            if ($userId <= 0) {
                throw new WorkflowDefinitionError('approval.user_id required');
            }

            return [['user_id' => $userId]];
        }

        if ($assigneeType === 'field') {
            $field = (string) ($approval['user_field'] ?? '');
            $rows = $env->browse($instanceRow['res_model'], [(int) $instanceRow['res_id']])->read([$field]);
            $val = (int) ($rows[0][$field] ?? 0);

            return $val > 0 ? [['user_id' => $val]] : [];
        }

        $groupId = (int) ($approval['group_id'] ?? 0);

        if ($groupId <= 0) {
            $admin = $env->model('res.groups')->search([['name', '=', 'Admin']], limit: 1);

            if ($admin->count() > 0) {
                $groupId = $admin->ids()[0];
            }
        }

        if ($groupId <= 0) {
            return [];
        }

        if (in_array($strategy, ['all', 'sequential'], true)) {
            $userIds = self::userIdsInGroup($env, $groupId);

            if ($userIds !== []) {
                return array_map(static fn (int $id): array => ['user_id' => $id], $userIds);
            }
        }

        return [['group_id' => $groupId]];
    }

    /**
     * @param  array<string, mixed>  $instanceRow
     * @return array<string, mixed>
     */
    public static function definitionForInstance(Environment $env, array $instanceRow): array
    {
        $defId = (int) ($instanceRow['definition_id'] ?? 0);
        $rows = $env->browse('workflow.definition', [$defId])->read(['definition']);

        return WorkflowParser::parse((string) ($rows[0]['definition'] ?? '{}'));
    }

    /**
     * @return array<string, mixed>
     */
    public static function reloadInstance(Environment $env, int $instanceId): array
    {
        $rows = $env->browse('workflow.instance', [$instanceId])->read();

        if ($rows === []) {
            throw new WorkflowDefinitionError("workflow.instance({$instanceId}) not found");
        }

        return $rows[0];
    }

    /**
     * @param  array<string, mixed>  $instanceRow
     * @param  array<string, mixed>  $tr
     * @param  array<string, mixed>  $stageData
     */
    private static function startApproval(
        Environment $env,
        array $instanceRow,
        array $tr,
        array $stageData,
        int $requesterId,
    ): void {
        $approvalCfg = is_array($tr['approval'] ?? null) ? $tr['approval'] : [];
        $strategy = $approvalCfg['strategy'] ?? 'any';
        $assignees = self::resolveAssignees($env, $instanceRow, $tr, $requesterId);

        if ($assignees === []) {
            throw new WorkflowDefinitionError('No approvers resolved for this transition');
        }

        $deadline = self::approvalDeadline($approvalCfg);
        $instanceId = (int) $instanceRow['id'];

        if ($strategy === 'sequential') {
            $queue = array_slice($assignees, 1);
            $stageData['_wf_queue'] = $queue;
            self::createApproval($env, $instanceId, $tr, $assignees[0], $requesterId, $stageData, 1, $deadline);
            $env->browse('workflow.instance', [$instanceId])->write([
                'stage_data' => json_encode($stageData, JSON_THROW_ON_ERROR),
            ]);

            return;
        }

        foreach ($assignees as $i => $spec) {
            self::createApproval($env, $instanceId, $tr, $spec, $requesterId, $stageData, $i + 1, $deadline);
        }
    }

    /**
     * @param  array{user_id?: int, group_id?: int}  $spec
     * @param  array<string, mixed>  $stageData
     */
    private static function createApproval(
        Environment $env,
        int $instanceId,
        array $tr,
        array $spec,
        int $requesterId,
        array $stageData,
        int $sequence,
        ?string $deadline,
    ): void {
        $cleanStage = [];

        foreach ($stageData as $k => $v) {
            if (! str_starts_with((string) $k, '_wf')) {
                $cleanStage[$k] = $v;
            }
        }

        $values = [
            'instance_id' => $instanceId,
            'transition_key' => $tr['key'],
            'status' => 'pending',
            'requester_id' => $requesterId > 0 ? $requesterId : null,
            'sequence' => $sequence,
            'form_data' => json_encode($cleanStage, JSON_THROW_ON_ERROR),
            'deadline_at' => $deadline,
        ];

        if (isset($spec['user_id'])) {
            $values['assignee_user_id'] = $spec['user_id'];
        }

        if (isset($spec['group_id'])) {
            $values['assignee_group_id'] = $spec['group_id'];
        }

        $env->model('workflow.approval')->create($values);

        if (isset($spec['user_id']) && $env->registry->has('workflow.task')) {
            $env->model('workflow.task')->create([
                'name' => 'Approve: '.($tr['label'] ?? $tr['key']),
                'description' => "Workflow approval on {$tr['key']}",
                'user_id' => $spec['user_id'],
                'state' => 'open',
                'priority' => 'high',
                'res_model' => null,
                'res_id' => null,
                'instance_id' => $instanceId,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $approvalCfg
     */
    private static function approvalDeadline(array $approvalCfg): ?string
    {
        $hours = $approvalCfg['deadline_hours'] ?? null;

        if ($hours === null || $hours === '') {
            return null;
        }

        try {
            return gmdate('Y-m-d H:i:s', time() + ((int) $hours) * 3600);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $instanceRow
     * @param  array<string, mixed>  $tr
     */
    private static function maybeAdvanceSequential(Environment $env, array $instanceRow, array $tr): void
    {
        $approvalCfg = is_array($tr['approval'] ?? null) ? $tr['approval'] : [];

        if (($approvalCfg['strategy'] ?? '') !== 'sequential') {
            return;
        }

        $stageData = WorkflowParser::loadJson($instanceRow['stage_data'] ?? '{}');
        $queue = $stageData['_wf_queue'] ?? [];

        if (! is_array($queue) || $queue === []) {
            return;
        }

        $nextSpec = array_shift($queue);
        $stageData['_wf_queue'] = $queue;
        $instanceId = (int) $instanceRow['id'];
        $env->browse('workflow.instance', [$instanceId])->write([
            'stage_data' => json_encode($stageData, JSON_THROW_ON_ERROR),
        ]);

        $pendingCount = $env->model('workflow.approval')->search([
            ['instance_id', '=', $instanceId],
            ['transition_key', '=', $tr['key']],
        ])->count();

        if (is_array($nextSpec)) {
            self::createApproval(
                $env,
                $instanceId,
                $tr,
                $nextSpec,
                (int) ($env->uid ?? Environment::SUPERUSER_ID),
                $stageData,
                $pendingCount + 1,
                self::approvalDeadline($approvalCfg),
            );
        }
    }

    /**
     * @param  array<string, mixed>  $instanceRow
     * @param  array<string, mixed>  $tr
     */
    private static function approvalsComplete(Environment $env, array $instanceRow, array $tr): bool
    {
        $instanceId = (int) $instanceRow['id'];
        $transitionKey = (string) $tr['key'];

        $pending = $env->model('workflow.approval')->search([
            ['instance_id', '=', $instanceId],
            ['transition_key', '=', $transitionKey],
            ['status', '=', 'pending'],
        ]);

        if ($pending->count() > 0) {
            return false;
        }

        $stageData = WorkflowParser::loadJson($instanceRow['stage_data'] ?? '{}');

        if (! empty($stageData['_wf_queue'])) {
            return false;
        }

        $strategy = ($tr['approval'] ?? [])['strategy'] ?? 'any';
        $approvals = $env->model('workflow.approval')->search([
            ['instance_id', '=', $instanceId],
            ['transition_key', '=', $transitionKey],
        ])->read(['status']);

        $approved = 0;
        $rejected = 0;

        foreach ($approvals as $row) {
            match ($row['status'] ?? '') {
                'approved' => $approved++,
                'rejected' => $rejected++,
                default => null,
            };
        }

        if ($rejected > 0) {
            return false;
        }

        if ($strategy === 'all') {
            return $approved === count($approvals) && $approved > 0;
        }

        return $approved >= 1;
    }

    /**
     * @param  array<string, mixed>  $tr
     * @param  array<string, mixed>  $formValues
     */
    private static function validateForm(array $tr, array $formValues): void
    {
        $form = is_array($tr['form'] ?? null) ? $tr['form'] : [];
        $fields = is_array($form['fields'] ?? null) ? $form['fields'] : [];

        foreach ($fields as $ff) {
            if (! is_array($ff) || empty($ff['required'])) {
                continue;
            }

            $name = (string) ($ff['name'] ?? '');
            $val = $formValues[$name] ?? null;

            if ($val === null || $val === '' || $val === false) {
                throw new WorkflowDefinitionError(($ff['label'] ?? $name).' is required');
            }
        }
    }

    /**
     * @param  array<string, mixed>  $tr
     * @param  array<string, mixed>  $formValues
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    private static function splitFormValues(array $tr, array $formValues): array
    {
        $stage = [];
        $record = [];
        $form = is_array($tr['form'] ?? null) ? $tr['form'] : [];
        $fieldMap = [];

        foreach ($form['fields'] ?? [] as $ff) {
            if (is_array($ff) && isset($ff['name'])) {
                $fieldMap[(string) $ff['name']] = $ff;
            }
        }

        foreach ($formValues as $key => $val) {
            $spec = $fieldMap[$key] ?? null;

            if ($spec === null) {
                continue;
            }

            if (($spec['source'] ?? 'stage') === 'record') {
                $record[$key] = $val;
            } else {
                $stage[$key] = $val;
            }
        }

        return [$stage, $record];
    }

    /**
     * @return list<int>
     */
    private static function userIdsInGroup(Environment $env, int $groupId): array
    {
        if (! $env->registry->has('res.users')) {
            return [];
        }

        $ids = [];

        foreach ($env->model('res.users')->search()->read(['id', 'group_ids']) as $row) {
            $groups = $row['group_ids'] ?? [];

            if (is_array($groups) && in_array($groupId, array_map(intval(...), $groups), true)) {
                $ids[] = (int) $row['id'];
            }
        }

        return $ids;
    }
}
