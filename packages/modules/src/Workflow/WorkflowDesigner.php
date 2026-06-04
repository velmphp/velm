<?php

declare(strict_types=1);

namespace Velm\Modules\Workflow;

use Velm\Environment;
use Velm\Exception\AccessDeniedException;
use Velm\Fields\Many2oneField;
use Velm\Fields\Field;

final class WorkflowDesigner
{
    /**
     * @return list<array{value: string, label: string}>
     */
    public static function listModels(Environment $env): array
    {
        $out = [];

        foreach (array_keys($env->registry->models()) as $name) {
            try {
                $env->checkAccess($name, 'read');
            } catch (AccessDeniedException) {
                continue;
            }

            $label = str_replace('.', ' ', $name);
            $out[] = ['value' => $name, 'label' => $label];
        }

        usort($out, static fn (array $a, array $b): int => strcmp($a['label'], $b['label']));

        return $out;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function listModelFields(Environment $env, string $modelName): array
    {
        if (! $env->registry->has($modelName)) {
            return [];
        }

        try {
            $env->checkAccess($modelName, 'read');
            $env->checkAccess($modelName, 'write');
        } catch (AccessDeniedException) {
            return [];
        }

        $fields = $env->registry->fieldSet($modelName);
        $out = [];

        foreach ($fields as $fname => $field) {
            if (in_array($fname, ['id', 'display_name', 'created_at', 'updated_at'], true)) {
                continue;
            }

            if ($field->readonly ?? false) {
                continue;
            }

            $ftype = self::fieldTypeLabel($field);
            $comodel = $field instanceof Many2oneField ? $field->comodel : null;

            $out[] = [
                'name' => $fname,
                'label' => $field->label ?? str_replace('_', ' ', ucfirst($fname)),
                'type' => $ftype,
                'source' => 'record',
                'comodel' => $comodel,
            ];
        }

        usort($out, static fn (array $a, array $b): int => strcmp((string) $a['label'], (string) $b['label']));

        return $out;
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    public static function listGroups(Environment $env): array
    {
        if (! $env->registry->has('res.groups')) {
            return [];
        }

        try {
            $env->checkAccess('res.groups', 'read');
        } catch (AccessDeniedException) {
            return [];
        }

        $out = [];

        foreach ($env->model('res.groups')->search([], order: '"name" ASC')->read(['id', 'name']) as $row) {
            $out[] = [
                'id' => (int) ($row['id'] ?? 0),
                'name' => (string) ($row['name'] ?? ''),
            ];
        }

        return $out;
    }

    /**
     * @return list<array{id: int, name: string, login: string}>
     */
    public static function listUsers(Environment $env): array
    {
        if (! $env->registry->has('res.users')) {
            return [];
        }

        try {
            $env->checkAccess('res.users', 'read');
        } catch (AccessDeniedException) {
            return [];
        }

        $out = [];

        foreach ($env->model('res.users')->search([], order: '"name" ASC')->read(['id', 'name', 'email']) as $row) {
            $out[] = [
                'id' => (int) ($row['id'] ?? 0),
                'name' => (string) ($row['name'] ?? ''),
                'login' => (string) ($row['email'] ?? ''),
            ];
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    public static function builderConfig(Environment $env, ?array $definitionRow = null): array
    {
        $config = [
            'workflowId' => null,
            'meta' => ['name' => '', 'description' => '', 'model' => '', 'active' => true],
            'definition' => [
                'version' => 1,
                'model' => '',
                'states' => [
                    ['key' => 'draft', 'label' => 'Draft', 'initial' => true, '_uid' => 'w1'],
                    ['key' => 'done', 'label' => 'Done', 'final' => true, '_uid' => 'w2'],
                ],
                'transitions' => [],
            ],
            'models' => self::listModels($env),
            'groups' => self::listGroups($env),
            'users' => self::listUsers($env),
            'recordFields' => [],
        ];

        if ($definitionRow === null) {
            return $config;
        }

        $defn = WorkflowParser::parse((string) ($definitionRow['definition'] ?? '{}'));
        $config['workflowId'] = (int) ($definitionRow['id'] ?? 0);
        $config['meta'] = [
            'name' => (string) ($definitionRow['name'] ?? ''),
            'description' => (string) ($definitionRow['description'] ?? ''),
            'model' => (string) ($definitionRow['model'] ?? ''),
            'active' => (bool) ($definitionRow['active'] ?? true),
        ];
        $config['definition'] = $defn;

        if (! empty($defn['auto_start'])) {
            $config['definition']['auto_start'] = true;
        }

        $model = (string) ($definitionRow['model'] ?? '');

        if ($model !== '') {
            $config['recordFields'] = self::listModelFields($env, $model);
        }

        return $config;
    }

    private static function fieldTypeLabel(Field $field): string
    {
        $short = (new \ReflectionClass($field))->getShortName();

        return strtolower(preg_replace('/Field$/', '', $short) ?? 'char');
    }
}
