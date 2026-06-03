<?php

declare(strict_types=1);

namespace Velm\Console\Scaffold;

use Velm\Fields\BooleanField;
use Velm\Fields\Field;
use Velm\Fields\Many2manyField;
use Velm\Fields\One2manyField;
use Velm\Registry;

final class ViewScaffoldBuilder
{
    private const SKIP_FIELDS = [
        'id',
        'display_name',
        'create_uid',
        'write_uid',
        'create_date',
        'write_date',
        'created_at',
        'updated_at',
    ];

    /**
     * @return array{list: list<string>, sections: list<array{id: string, title: string, fields: list<string>}>}
     */
    public function build(Registry $registry, string $technical, int $maxListFields = 12): array
    {
        if (! $registry->hasFieldSet($technical)) {
            throw new \InvalidArgumentException(
                "Model {$technical} is not registered — check the module name and that models are loadable.",
            );
        }

        $fields = $registry->fieldSet($technical);
        $scalars = [];
        $relations = [];

        foreach ($fields as $name => $field) {
            if (in_array($name, self::SKIP_FIELDS, true)) {
                continue;
            }

            if ($field instanceof Many2manyField || $field instanceof One2manyField) {
                $relations[] = [$name, $field];

                continue;
            }

            $scalars[] = [$name, $field];
        }

        usort($scalars, static function (array $a, array $b): int {
            $order = static fn (string $name): int => match ($name) {
                'name' => 0,
                'active' => 1,
                default => 2,
            };

            $cmp = $order($a[0]) <=> $order($b[0]);

            return $cmp !== 0 ? $cmp : $a[0] <=> $b[0];
        });

        usort($relations, static fn (array $a, array $b): int => $a[0] <=> $b[0]);

        $list = [];

        foreach ([...$scalars, ...$relations] as [$name, $field]) {
            if (count($list) >= $maxListFields) {
                break;
            }

            $list[] = $this->columnExpression($name, $field);
        }

        if ($list === []) {
            $list = isset($fields['name'])
                ? [$this->columnExpression('name', $fields['name'])]
                : ["'id'"];
        }

        $sections = [];

        if ($scalars !== []) {
            $sections[] = [
                'id' => 'main',
                'title' => $this->titleFromModel($technical),
                'fields' => array_map(
                    fn (array $pair): string => $this->columnExpression($pair[0], $pair[1]),
                    $scalars,
                ),
            ];
        }

        if ($relations !== []) {
            $sections[] = [
                'id' => 'relations',
                'title' => 'Relations',
                'fields' => array_map(
                    fn (array $pair): string => $this->columnExpression($pair[0], $pair[1]),
                    $relations,
                ),
            ];
        }

        if ($sections === []) {
            $sections[] = [
                'id' => 'main',
                'title' => $this->titleFromModel($technical),
                'fields' => $list,
            ];
        }

        return ['list' => $list, 'sections' => $sections];
    }

    private function columnExpression(string $name, Field $field): string
    {
        if ($field instanceof BooleanField) {
            return "Field::make('{$name}')->toggle()";
        }

        return "'{$name}'";
    }

    private function titleFromModel(string $technical): string
    {
        $stem = explode('.', $technical);
        $stem = end($stem) ?: $technical;

        return implode(' ', array_map(
            static fn (string $part): string => $part !== '' ? ucfirst($part) : '',
            explode('_', $stem),
        ));
    }
}
