<?php

declare(strict_types=1);

namespace Velm\Admin\Arch;

use Velm\Environment;
use Velm\Fields\BooleanField;
use Velm\Fields\CharField;
use Velm\Fields\DatetimeField;
use Velm\Fields\IntegerField;
use Velm\Fields\Many2oneField;
use Velm\Fields\TextField;

class ViewFieldCatalog
{
    /**
     * @return array{
     *     groupable: list<array{value: string, label: string, type: string}>,
     *     measurable: list<array{value: string, label: string, type: string}>
     * }
     */
    public function forModel(string $model, Environment $env): array
    {
        if (! $env->registry->has($model)) {
            throw new \InvalidArgumentException("Unknown model {$model}.");
        }

        $modelClass = $env->registry->modelClass($model);
        $groupable = [];
        $measurable = [
            ['value' => '__count', 'label' => 'Count', 'type' => 'count'],
        ];

        foreach ($modelClass::fields() as $name => $field) {
            if (! $field->persistsInDatabase()) {
                continue;
            }

            $label = $field->displayLabel();
            $type = class_basename($field);

            if ($field instanceof Many2oneField || $field instanceof DatetimeField) {
                $groupable[] = ['value' => $name, 'label' => $label, 'type' => $type];

                if ($field instanceof DatetimeField) {
                    foreach (['day', 'month', 'year'] as $granularity) {
                        $groupable[] = [
                            'value' => $name.':'.$granularity,
                            'label' => $label.' ('.$granularity.')',
                            'type' => $type,
                        ];
                    }
                }

                continue;
            }

            if ($field instanceof BooleanField) {
                $groupable[] = ['value' => $name, 'label' => $label, 'type' => $type];

                continue;
            }

            if ($field instanceof CharField || $field instanceof TextField || $field instanceof IntegerField) {
                $groupable[] = ['value' => $name, 'label' => $label, 'type' => $type];
            }

            if ($field instanceof IntegerField) {
                $measurable[] = ['value' => $name.':sum', 'label' => $label.' (sum)', 'type' => $type];
                $measurable[] = ['value' => $name.':avg', 'label' => $label.' (avg)', 'type' => $type];
            }
        }

        return [
            'groupable' => $groupable,
            'measurable' => $measurable,
        ];
    }
}
