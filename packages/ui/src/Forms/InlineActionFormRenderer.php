<?php

declare(strict_types=1);

namespace Velm\Ui\Forms;

use Velm\Environment;
use Velm\Fields\BooleanField;
use Velm\Fields\CharField;
use Velm\Fields\Field;
use Velm\Fields\IntegerField;
use Velm\Fields\Many2oneField;
use Velm\Fields\TextField;
use Velm\Views\Arch\ArchNormalizer;

final class InlineActionFormRenderer
{
    /**
     * @param  array{sections: list<array<string, mixed>>, cols?: int, model?: string}  $formArch
     * @param  array<string, mixed>  $values
     * @return list<array{name: string, label: string, type: string, required: bool, value: mixed, options?: list<array{id: int|string, label: string}>, multiline?: bool}>
     */
    public function fields(Environment $env, string $model, array $formArch, array $values = []): array
    {
        $arch = ArchNormalizer::normalizeForm([
            'model' => $model,
            'sections' => $formArch['sections'] ?? [],
        ]);
        $fields = [];

        foreach ($arch['sections'] as $section) {
            if (! is_array($section)) {
                continue;
            }

            $sectionFields = $section['fields'] ?? $section['pages'] ?? [];

            if (isset($section['pages']) && is_array($section['pages'])) {
                foreach ($section['pages'] as $page) {
                    if (! is_array($page)) {
                        continue;
                    }

                    foreach ($page['fields'] ?? [] as $fieldSpec) {
                        $field = $this->fieldSpec($env, $model, $fieldSpec, $values);

                        if ($field !== null) {
                            $fields[] = $field;
                        }
                    }
                }

                continue;
            }

            if (! is_array($sectionFields)) {
                continue;
            }

            foreach ($sectionFields as $fieldSpec) {
                $field = $this->fieldSpec($env, $model, $fieldSpec, $values);

                if ($field !== null) {
                    $fields[] = $field;
                }
            }
        }

        return $fields;
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array{name: string, label: string, type: string, required: bool, value: mixed, options?: list<array{id: int|string, label: string}>, multiline?: bool}|null
     */
    private function fieldSpec(Environment $env, string $model, mixed $fieldSpec, array $values): ?array
    {
        if (! is_array($fieldSpec) || ! isset($fieldSpec['name'])) {
            return null;
        }

        $name = (string) $fieldSpec['name'];
        $velmField = $env->registry->field($model, $name);
        $label = is_string($fieldSpec['label'] ?? null) && $fieldSpec['label'] !== ''
            ? (string) $fieldSpec['label']
            : ($velmField?->displayLabel() ?? Field::humanizeFieldName($name));
        $required = $velmField?->required === true;
        $value = array_key_exists($name, $values) ? $values[$name] : null;
        $widget = is_string($fieldSpec['widget'] ?? null) ? (string) $fieldSpec['widget'] : null;

        if ($velmField instanceof BooleanField || $widget === 'toggle') {
            return [
                'name' => $name,
                'label' => $label,
                'type' => 'boolean',
                'required' => $required,
                'value' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            ];
        }

        if ($velmField instanceof Many2oneField) {
            $options = [];
            $domain = $env->registry->modelClass($velmField->comodel)::relationalSearchDomain();
            $rows = $env->model($velmField->comodel)->search($domain, limit: 200, order: 'name asc')->read(['display_name']);

            foreach ($rows as $row) {
                $options[] = [
                    'id' => $row['id'],
                    'label' => (string) ($row['display_name'] ?? $row['id']),
                ];
            }

            return [
                'name' => $name,
                'label' => $label,
                'type' => 'many2one',
                'required' => $required,
                'value' => $value,
                'options' => $options,
            ];
        }

        if ($velmField instanceof TextField || $widget === 'text') {
            return [
                'name' => $name,
                'label' => $label,
                'type' => 'text',
                'required' => $required,
                'value' => $value,
                'multiline' => true,
            ];
        }

        if ($velmField instanceof IntegerField) {
            return [
                'name' => $name,
                'label' => $label,
                'type' => 'integer',
                'required' => $required,
                'value' => $value,
            ];
        }

        if ($velmField instanceof CharField || $velmField === null) {
            return [
                'name' => $name,
                'label' => $label,
                'type' => 'char',
                'required' => $required,
                'value' => $value,
            ];
        }

        return [
            'name' => $name,
            'label' => $label,
            'type' => 'char',
            'required' => $required,
            'value' => is_scalar($value) || $value === null ? $value : json_encode($value),
        ];
    }
}
