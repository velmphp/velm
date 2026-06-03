<?php

declare(strict_types=1);

namespace Velm\Admin\Arch;

use Velm\Environment;
use Velm\Fields\BooleanField;
use Velm\Fields\CharField;
use Velm\Fields\Field;
use Velm\Fields\Many2oneField;
use Velm\Fields\TextField;
use Velm\Views\Arch\ArchNormalizer;

final class ListDomainBuilder
{
    /**
     * @param  array<string, mixed>  $arch
     * @return list<mixed>|list<list<mixed>>
     */
    public function build(array $arch, Environment $env, ListQuery $query): array
    {
        $arch = ArchNormalizer::normalizeList($arch);
        $model = (string) ($arch['model'] ?? '');
        $modelClass = $env->registry->modelClass($model);
        $staticDomain = $arch['domain'] ?? [];
        $domain = is_array($staticDomain) ? $staticDomain : [];

        $search = trim($query->search);
        if ($search !== '') {
            $searchDomain = $this->searchDomain($modelClass::fields(), $arch['fields'], $search);
            if ($searchDomain !== []) {
                $domain[] = $searchDomain;
            }
        }

        foreach ($query->filterChips as $chip) {
            $leaf = $this->chipToLeaf($chip, $arch['fields'], $modelClass::fields());
            if ($leaf !== null) {
                $domain[] = $leaf;
            }
        }

        return $domain;
    }

    /**
     * @param  array<string, Field>  $modelFields
     * @param  list<array<string, mixed>>  $fieldsSpec
     * @return list<mixed>
     */
    private function searchDomain(array $modelFields, array $fieldsSpec, string $search): array
    {
        $term = '%'.$search.'%';
        $visible = array_column($fieldsSpec, 'name');
        $candidates = [];

        foreach ($modelFields as $name => $field) {
            if (! ($field instanceof CharField || $field instanceof TextField)) {
                continue;
            }

            if (in_array($name, $visible, true)) {
                $candidates[] = [$name, 'ilike', $term];
            }
        }

        if ($candidates === []) {
            foreach ($modelFields as $name => $field) {
                if ($field instanceof CharField || $field instanceof TextField) {
                    $candidates[] = [$name, 'ilike', $term];
                }
            }
        }

        if ($candidates === []) {
            return [];
        }

        return ['__or__', 'ilike', $candidates];
    }

    /**
     * @param  array{field: ?string, op: string, value: mixed, label: string}  $chip
     * @param  list<array<string, mixed>>  $fieldsSpec
     * @param  array<string, Field>  $modelFields
     * @return list<mixed>|null
     */
    private function chipToLeaf(array $chip, array $fieldsSpec, array $modelFields): ?array
    {
        $fieldName = $chip['field'] ?? null;
        if (! is_string($fieldName) || $fieldName === '') {
            return null;
        }

        $allowed = array_column($fieldsSpec, 'name');
        if (! in_array($fieldName, $allowed, true) && $fieldName !== 'id') {
            return null;
        }

        $value = $chip['value'] ?? null;
        if ($value === null || $value === '') {
            return null;
        }

        $op = (string) ($chip['op'] ?? '=');
        $field = $modelFields[$fieldName] ?? null;

        if ($fieldName === 'id') {
            if (! is_numeric($value)) {
                return null;
            }

            return ['id', '=', (int) $value];
        }

        if ($field === null) {
            return null;
        }

        if ($field instanceof BooleanField) {
            return [$fieldName, '=', filter_var($value, FILTER_VALIDATE_BOOLEAN)];
        }

        if ($field instanceof Many2oneField) {
            return [$fieldName, '=', (int) $value];
        }

        if ($field instanceof CharField || $field instanceof TextField) {
            $needle = is_string($value) && str_contains($value, '%') ? $value : '%'.$value.'%';

            return [$fieldName, 'ilike', $needle];
        }

        if (! in_array($op, ['=', '!=', '>', '>=', '<', '<='], true)) {
            return null;
        }

        return [$fieldName, $op, $value];
    }
}
