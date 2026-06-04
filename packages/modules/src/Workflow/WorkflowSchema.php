<?php

declare(strict_types=1);

namespace Velm\Modules\Workflow;

use Velm\Registry;

final class WorkflowSchema
{
    public const int WORKFLOW_VERSION = 1;

    /**
     * @param  array<string, mixed>  $defn
     */
    public static function validate(array $defn, Registry $registry): void
    {
        if ($defn === []) {
            throw new WorkflowDefinitionError('Definition must be a JSON object');
        }

        $version = $defn['version'] ?? 1;

        if ((int) $version !== self::WORKFLOW_VERSION) {
            throw new WorkflowDefinitionError(
                'Unsupported workflow version '.(string) $version.' (expected '.self::WORKFLOW_VERSION.')',
            );
        }

        $model = $defn['model'] ?? null;

        if (! is_string($model) || $model === '') {
            throw new WorkflowDefinitionError("'model' must be a model technical name");
        }

        if (! $registry->has($model)) {
            throw new WorkflowDefinitionError("Unknown model {$model}");
        }

        $states = $defn['states'] ?? null;

        if (! is_array($states) || $states === []) {
            throw new WorkflowDefinitionError("'states' must be a non-empty list");
        }

        $stateKeys = [];
        $initialCount = 0;

        foreach ($states as $i => $st) {
            if (! is_array($st)) {
                throw new WorkflowDefinitionError("states[{$i}] must be an object");
            }

            $key = $st['key'] ?? null;

            if (! is_string($key) || $key === '') {
                throw new WorkflowDefinitionError("states[{$i}].key must be a string");
            }

            if (isset($stateKeys[$key])) {
                throw new WorkflowDefinitionError("Duplicate state key {$key}");
            }

            $stateKeys[$key] = true;
            $label = $st['label'] ?? null;

            if (! is_string($label) || $label === '') {
                throw new WorkflowDefinitionError("states[{$i}].label must be a string");
            }

            if (! empty($st['initial'])) {
                $initialCount++;
            }
        }

        if ($initialCount !== 1) {
            throw new WorkflowDefinitionError('Exactly one state must have initial=true');
        }

        if (isset($defn['auto_start']) && ! is_bool($defn['auto_start'])) {
            throw new WorkflowDefinitionError("'auto_start' must be a boolean");
        }

        $transitions = $defn['transitions'] ?? [];

        if (! is_array($transitions)) {
            throw new WorkflowDefinitionError("'transitions' must be a list");
        }

        $transKeys = [];
        $fields = $registry->fieldSet($model);

        foreach ($transitions as $i => $tr) {
            if (! is_array($tr)) {
                throw new WorkflowDefinitionError("transitions[{$i}] must be an object");
            }

            $tkey = $tr['key'] ?? null;

            if (! is_string($tkey) || $tkey === '') {
                throw new WorkflowDefinitionError("transitions[{$i}].key must be a string");
            }

            if (isset($transKeys[$tkey])) {
                throw new WorkflowDefinitionError("Duplicate transition key {$tkey}");
            }

            $transKeys[$tkey] = true;
            $label = $tr['label'] ?? null;

            if (! is_string($label) || $label === '') {
                throw new WorkflowDefinitionError("transitions[{$i}].label must be a string");
            }

            $toState = $tr['to'] ?? null;

            if (! is_string($toState) || ! isset($stateKeys[$toState])) {
                throw new WorkflowDefinitionError("transitions[{$i}].to must reference a defined state");
            }

            $fromStates = $tr['from'] ?? [];

            if (! is_array($fromStates) || $fromStates === []) {
                throw new WorkflowDefinitionError("transitions[{$i}].from must be a non-empty list");
            }

            foreach ($fromStates as $fs) {
                if (! is_string($fs) || ! isset($stateKeys[$fs])) {
                    throw new WorkflowDefinitionError("transitions[{$i}].from references unknown state");
                }
            }

            $kind = $tr['kind'] ?? 'user';

            if (! in_array($kind, ['user', 'approval', 'automatic'], true)) {
                throw new WorkflowDefinitionError("transitions[{$i}].kind {$kind} invalid");
            }

            $rejectTo = $tr['reject_to'] ?? null;

            if ($rejectTo !== null && (! is_string($rejectTo) || ! isset($stateKeys[$rejectTo]))) {
                throw new WorkflowDefinitionError("transitions[{$i}].reject_to must reference a defined state");
            }

            if ($kind === 'approval') {
                $approval = $tr['approval'] ?? [];

                if (! is_array($approval)) {
                    throw new WorkflowDefinitionError("transitions[{$i}].approval must be an object");
                }

                $strategy = $approval['strategy'] ?? 'any';

                if (! in_array($strategy, ['any', 'all', 'sequential'], true)) {
                    throw new WorkflowDefinitionError("transitions[{$i}].approval.strategy invalid");
                }

                $assigneeType = $approval['assignee_type'] ?? 'group';

                if (! in_array($assigneeType, ['user', 'group', 'field'], true)) {
                    throw new WorkflowDefinitionError("transitions[{$i}].approval.assignee_type invalid");
                }

                if ($assigneeType === 'field') {
                    $uf = $approval['user_field'] ?? null;

                    if (! is_string($uf) || $uf === '' || ! isset($fields[$uf])) {
                        throw new WorkflowDefinitionError("transitions[{$i}].approval.user_field must be a model field");
                    }
                }
            }

            self::validateTransitionForm($tr, $i, $fields);
        }
    }

    /**
     * @param  array<string, mixed>  $tr
     * @param  array<string, \Velm\Fields\Field>  $modelFields
     */
    private static function validateTransitionForm(array $tr, int $index, array $modelFields): void
    {
        $form = $tr['form'] ?? null;

        if ($form === null) {
            return;
        }

        if (! is_array($form)) {
            throw new WorkflowDefinitionError("transitions[{$index}].form must be an object");
        }

        $formFields = $form['fields'] ?? [];

        if (! is_array($formFields)) {
            throw new WorkflowDefinitionError("transitions[{$index}].form.fields must be a list");
        }

        $seen = [];

        foreach ($formFields as $j => $ff) {
            if (! is_array($ff)) {
                throw new WorkflowDefinitionError("transitions[{$index}].form.fields[{$j}] must be an object");
            }

            $name = $ff['name'] ?? null;

            if (! is_string($name) || $name === '') {
                throw new WorkflowDefinitionError("transitions[{$index}].form.fields[{$j}].name required");
            }

            if (isset($seen[$name])) {
                throw new WorkflowDefinitionError("Duplicate form field {$name}");
            }

            $seen[$name] = true;
            $source = $ff['source'] ?? 'stage';

            if (! in_array($source, ['record', 'stage'], true)) {
                throw new WorkflowDefinitionError("transitions[{$index}].form.fields[{$j}].source invalid");
            }

            if ($source === 'record') {
                if (! isset($modelFields[$name])) {
                    throw new WorkflowDefinitionError("transitions[{$index}].form.fields[{$j}] unknown record field {$name}");
                }
            } else {
                $ftype = $ff['type'] ?? 'char';

                if (! in_array($ftype, ['char', 'text', 'integer', 'float', 'boolean', 'date', 'datetime', 'selection'], true)) {
                    throw new WorkflowDefinitionError("transitions[{$index}].form.fields[{$j}].type invalid");
                }
            }
        }
    }
}
