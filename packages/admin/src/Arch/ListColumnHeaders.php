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

final class ListColumnHeaders
{
    /**
     * @param  array<string, mixed>  $arch
     * @return list<array{name: string, label: string, filter_kind: string, group_kind: string, comodel: ?string, visible_default: bool}>
     */
    public function fromArch(array $arch, Environment $env): array
    {
        $arch = ArchNormalizer::normalizeList($arch);
        $model = (string) ($arch['model'] ?? '');
        $headers = [];

        foreach ($arch['fields'] as $spec) {
            $name = $spec['name'];
            $field = $env->registry->field($model, $name);
            $label = is_string($spec['label'] ?? null) ? $spec['label'] : null;

            if ($label === null && $field instanceof Field) {
                $label = $field->displayLabel();
            }

            $filterKind = 'none';
            $groupKind = 'none';
            $comodel = null;

            if ($field instanceof Many2oneField) {
                $filterKind = 'm2o';
                $groupKind = 'm2o';
                $comodel = $field->comodel;
            } elseif ($field instanceof BooleanField) {
                $filterKind = 'boolean';
                $groupKind = 'boolean';
            } elseif ($field instanceof CharField || $field instanceof TextField) {
                $filterKind = 'text';
            }

            $headers[] = [
                'name' => $name,
                'label' => $label ?? $name,
                'filter_kind' => $filterKind,
                'group_kind' => $groupKind,
                'comodel' => $comodel,
                'visible_default' => ($spec['visible'] ?? true) !== false,
            ];
        }

        return $headers;
    }

    /**
     * @return list<array{name: string, label: string, filter_kind: string, group_kind: string, comodel: ?string, visible_default: bool}>
     */
    public function fromModel(string $model, Environment $env): array
    {
        $headers = [];

        foreach ($env->registry->fieldsFor($model) as $name => $field) {
            if (! $field->persistsInDatabase()) {
                continue;
            }

            $label = $field->displayLabel();
            $filterKind = 'none';
            $groupKind = 'none';
            $comodel = null;

            if ($field instanceof Many2oneField) {
                $filterKind = 'm2o';
                $groupKind = 'm2o';
                $comodel = $field->comodel;
            } elseif ($field instanceof BooleanField) {
                $filterKind = 'boolean';
                $groupKind = 'boolean';
            } elseif ($field instanceof CharField || $field instanceof TextField) {
                $filterKind = 'text';
            }

            $headers[] = [
                'name' => $name,
                'label' => $label !== '' ? $label : $name,
                'filter_kind' => $filterKind,
                'group_kind' => $groupKind,
                'comodel' => $comodel,
                'visible_default' => true,
            ];
        }

        return $headers;
    }
}
