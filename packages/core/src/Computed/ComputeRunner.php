<?php

declare(strict_types=1);

namespace Velm\Computed;

use Velm\Environment;
use Velm\Fields\Field;
use Velm\Recordset\Recordset;

final class ComputeRunner
{
    public function __construct(
        private readonly Environment $env,
    ) {}

    public function computeStoredOnCreate(Recordset $records): void
    {
        foreach ($this->env->registry->computedGraph()->storedOrder($records->modelName()) as $fieldName) {
            $this->compute($records, $fieldName);
        }
    }

    /**
     * @param  list<string>  $writtenFields
     */
    public function recomputeAfterWrite(Recordset $records, array $writtenFields): void
    {
        if ($writtenFields === []) {
            return;
        }

        $modelName = $records->modelName();
        $fields = $this->fieldsFor($records);

        foreach ($this->env->registry->computedGraph()->affectedComputedFields($modelName, $writtenFields) as $fieldName) {
            $field = $fields[$fieldName] ?? null;

            if ($field === null || ! $field->isComputed()) {
                continue;
            }

            if (! $field->isStored()) {
                foreach ($records->ids() as $id) {
                    $this->env->cache->forget($modelName, $id, $fieldName);
                }
            }

            $this->compute($records, $fieldName);
        }
    }

    public function compute(Recordset $records, string $fieldName): void
    {
        if ($records->ids() === []) {
            return;
        }

        $fields = $this->fieldsFor($records);
        $field = $fields[$fieldName] ?? null;

        if ($field === null || ! $field->isComputed()) {
            throw new \InvalidArgumentException("Field {$records->modelName()}.{$fieldName} is not computed.");
        }

        $method = $field->computeMethod();

        if ($method === null) {
            throw new \InvalidArgumentException("Field {$records->modelName()}.{$fieldName} has no compute method.");
        }

        /** @var array<int, mixed> $values */
        $values = $records->{$method}();

        if (! is_array($values)) {
            throw new \RuntimeException(
                "Compute {$records->modelName()}::{$method}() must return an array keyed by record id.",
            );
        }

        $modelName = $records->modelName();

        foreach ($records->ids() as $id) {
            if (! array_key_exists($id, $values)) {
                throw new \RuntimeException(
                    "Compute {$records->modelName()}::{$method}() did not return a value for id={$id}.",
                );
            }

            $value = $field->toPhp($values[$id]);
            $this->env->cache->set($modelName, $id, $fieldName, $value);

            if ($field->isStored()) {
                $this->persistStored($records, $id, $field, $value);
            }
        }
    }

    /**
     * Fill unstored computed values for a read result row.
     *
     * @param  list<string>  $fieldNames
     */
    public function fillUnstoredForRead(Recordset $records, array $fieldNames): void
    {
        $fields = $this->fieldsFor($records);
        $modelName = $records->modelName();

        foreach ($fieldNames as $fieldName) {
            $field = $fields[$fieldName] ?? null;

            if ($field === null || ! $field->isComputed() || $field->isStored()) {
                continue;
            }

            $missing = [];

            foreach ($records->ids() as $id) {
                if (! $this->env->cache->has($modelName, $id, $fieldName)) {
                    $missing[] = $id;
                }
            }

            if ($missing === []) {
                continue;
            }

            $this->compute(
                new Recordset($this->env, $this->env->registry->modelClass($modelName), $missing),
                $fieldName,
            );
        }
    }

    private function persistStored(Recordset $records, int $id, Field $field, mixed $value): void
    {
        $modelClass = $this->env->registry->modelClass($records->modelName());
        $sqlValue = $field->toSql($value);

        $this->env->connection->execute(
            'UPDATE "'.$modelClass::table().'" SET "'.$field->column.'" = ? WHERE "id" = ?',
            [$sqlValue, $id],
        );
    }

    /**
     * @return array<string, Field>
     */
    private function fieldsFor(Recordset $records): array
    {
        return $this->env->registry->fieldSet($records->modelName());
    }
}
