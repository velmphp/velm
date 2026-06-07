<?php

declare(strict_types=1);

namespace Velm\Admin\Arch;

use Velm\Environment;
use Velm\Fields\BooleanField;
use Velm\Fields\Field;
use Velm\Fields\Many2manyField;
use Velm\Fields\Many2oneField;
use Velm\Fields\One2manyField;
use Velm\Views\Arch\ArchNormalizer;

final class ArchSchemaBuilder
{
    /**
     * @param  array<string, mixed>  $arch
     * @return list<ListColumn>
     */
    public function buildListColumns(array $arch, Environment $env): array
    {
        $arch = ArchNormalizer::normalizeList($arch);
        $columns = [];
        $model = (string) ($arch['model'] ?? '');

        foreach ($arch['fields'] as $field) {
            $columns[] = $this->listColumnFor($field, $env, $model);
        }

        return $columns;
    }

    /**
     * @param  array<string, mixed>  $field
     */
    private function listColumnFor(array $field, Environment $env, string $model): ListColumn
    {
        $name = $field['name'];
        $widget = $field['widget'] ?? null;
        $velmField = $this->velmField($env, $model, $name);

        if ($widget === 'toggle' || $velmField instanceof BooleanField) {
            return new ListColumn($name, 'toggle');
        }

        if ($widget === 'file' && $velmField instanceof Many2oneField) {
            return new ListColumn($name, 'file', $velmField->comodel);
        }

        if ($widget === 'files' && $velmField instanceof Many2manyField) {
            return new ListColumn($name, 'files', $velmField->comodel);
        }

        if ($velmField instanceof Many2oneField) {
            return new ListColumn($name, 'm2o', $velmField->comodel);
        }

        if ($velmField instanceof Many2manyField) {
            return new ListColumn($name, 'm2m', $velmField->comodel);
        }

        if ($velmField instanceof One2manyField) {
            return new ListColumn($name, 'o2m', $velmField->comodel);
        }

        return new ListColumn($name, 'text');
    }

    /**
     * @param  array<string, mixed>  $fieldSpec
     */
    public function formatFieldValue(string $model, array $fieldSpec, mixed $value, Environment $env): string
    {
        return $this->formatListCell($this->listColumnFor($fieldSpec, $env, $model), $value, $env);
    }

    public function formatGroupLabel(string $model, string $fieldName, mixed $value, Environment $env): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        $field = $this->velmField($env, $model, $fieldName);

        if ($field instanceof BooleanField) {
            return $value ? 'Yes' : 'No';
        }

        if ($field instanceof Many2oneField) {
            if ($value === false || $value === 0) {
                return '—';
            }

            return $this->formatListCell(new ListColumn($fieldName, 'm2o', $field->comodel), $value, $env);
        }

        return (string) $value;
    }

    public function formatListCell(ListColumn $column, mixed $value, Environment $env): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if ($column->kind === 'm2o' && $column->comodel !== null) {
            $rows = $env->browse($column->comodel, [(int) $value])->read();

            return (string) ($rows[0]['display_name'] ?? $value);
        }

        if ($column->kind === 'file' && $column->comodel !== null) {
            return $this->formatListCell(new ListColumn($column->name, 'm2o', $column->comodel), $value, $env);
        }

        if (in_array($column->kind, ['m2m', 'o2m'], true) && $column->comodel !== null && is_array($value)) {
            return $this->formatRelationIds($value, $column->comodel, $env);
        }

        if ($column->kind === 'files' && $column->comodel !== null && is_array($value)) {
            return $this->formatListCell(new ListColumn($column->name, 'm2m', $column->comodel), $value, $env);
        }

        if ($column->kind === 'toggle') {
            return $value ? 'Yes' : 'No';
        }

        if (is_array($value)) {
            return implode(', ', array_map(strval(...), $value));
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        return (string) $value;
    }

    /**
     * @param  list<int|string>  $ids
     */
    private function formatRelationIds(array $ids, string $comodel, Environment $env): string
    {
        $ids = array_values(array_filter(array_map(intval(...), $ids)));

        if ($ids === []) {
            return '';
        }

        $rows = $env->browse($comodel, $ids)->read();
        $labels = array_map(
            static fn (array $row): string => (string) ($row['display_name'] ?? $row['id']),
            $rows,
        );

        return implode(', ', $labels);
    }

    private function velmField(Environment $env, string $model, string $name): ?Field
    {
        if ($model === '') {
            return null;
        }

        return $env->registry->field($model, $name);
    }
}
