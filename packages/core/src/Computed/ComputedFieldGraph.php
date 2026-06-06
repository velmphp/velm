<?php

declare(strict_types=1);

namespace Velm\Computed;

use Velm\Fields\Field;
use Velm\Registry;

/**
 * Dependency graph for computed fields on each registered model.
 */
final class ComputedFieldGraph
{
    /**
     * @param  array<string, list<string>>  $storedOrder
     * @param  array<string, array<string, list<string>>>  $dependents
     */
    private function __construct(
        private readonly array $storedOrder,
        private readonly array $dependents,
    ) {}

    public static function build(Registry $registry): self
    {
        /** @var array<string, list<string>> $storedOrder */
        $storedOrder = [];

        /** @var array<string, array<string, list<string>>> $dependents */
        $dependents = [];

        foreach ($registry->models() as $modelName => $modelClass) {
            $fields = $registry->fieldSet($modelName);
            $storedFields = [];

            foreach ($fields as $fieldName => $field) {
                if (! $field->isComputed()) {
                    continue;
                }

                $method = $field->computeMethod();

                if ($method === null || ! self::hasComputeMethod($registry, $modelName, $method)) {
                    throw new \InvalidArgumentException(
                        "Computed field {$modelName}.{$fieldName} references missing method {$method}().",
                    );
                }

                foreach ($field->dependsOn() as $dep) {
                    if (! str_contains($dep, '.') && ! isset($fields[$dep])) {
                        throw new \InvalidArgumentException(
                            "Computed field {$modelName}.{$fieldName} depends on unknown field {$dep}.",
                        );
                    }

                    $root = str_contains($dep, '.') ? explode('.', $dep, 2)[0] : $dep;

                    if (! isset($fields[$root])) {
                        throw new \InvalidArgumentException(
                            "Computed field {$modelName}.{$fieldName} depends on unknown field {$dep}.",
                        );
                    }

                    $dependents[$modelName][$root] ??= [];
                    $dependents[$modelName][$root][] = $fieldName;
                }

                if ($field->isStored()) {
                    $storedFields[] = $fieldName;
                }
            }

            if ($storedFields !== []) {
                $storedOrder[$modelName] = self::topoStoredOrder($modelName, $storedFields, $fields);
            }
        }

        return new self($storedOrder, $dependents);
    }

    public static function empty(): self
    {
        return new self([], []);
    }

    /**
     * @param  list<string>  $storedFields
     * @param  array<string, Field>  $fields
     * @return list<string>
     */
    private static function topoStoredOrder(string $modelName, array $storedFields, array $fields): array
    {
        $storedSet = array_fill_keys($storedFields, true);
        $order = [];
        $visiting = [];
        $done = [];

        $visit = function (string $fieldName) use (&$visit, &$order, &$visiting, &$done, $fields, $storedSet, $modelName): void {
            if (isset($done[$fieldName])) {
                return;
            }

            if (isset($visiting[$fieldName])) {
                throw new \InvalidArgumentException(
                    "Computed-field cycle on {$modelName}.{$fieldName}.",
                );
            }

            $visiting[$fieldName] = true;

            $field = $fields[$fieldName] ?? null;

            if ($field !== null) {
                foreach ($field->dependsOn() as $dep) {
                    $root = str_contains($dep, '.') ? explode('.', $dep, 2)[0] : $dep;

                    if (isset($storedSet[$root])) {
                        $visit($root);
                    }
                }
            }

            unset($visiting[$fieldName]);
            $done[$fieldName] = true;
            $order[] = $fieldName;
        };

        foreach ($storedFields as $fieldName) {
            $visit($fieldName);
        }

        return $order;
    }

    /**
     * @return list<string>
     */
    public function storedOrder(string $modelName): array
    {
        return $this->storedOrder[$modelName] ?? [];
    }

    /**
     * Computed field names to refresh when *field* was written.
     *
     * @return list<string>
     */
    public function dependents(string $modelName, string $field): array
    {
        $list = $this->dependents[$modelName][$field] ?? [];

        return array_values(array_unique($list));
    }

    /**
     * @param  list<string>  $writtenFields
     * @return list<string>
     */
    public function affectedComputedFields(string $modelName, array $writtenFields): array
    {
        $out = [];

        foreach ($writtenFields as $field) {
            foreach ($this->dependents($modelName, $field) as $computed) {
                $out[$computed] = true;
            }
        }

        $ordered = [];

        foreach ($this->storedOrder($modelName) as $stored) {
            if (isset($out[$stored])) {
                $ordered[] = $stored;
                unset($out[$stored]);
            }
        }

        foreach (array_keys($out) as $unstored) {
            $ordered[] = $unstored;
        }

        return $ordered;
    }

    private static function hasComputeMethod(Registry $registry, string $modelName, string $method): bool
    {
        foreach ($registry->extensionChainFor($modelName) as $class) {
            if ($class::isRecordMethod($method)) {
                return true;
            }
        }

        return false;
    }
}
