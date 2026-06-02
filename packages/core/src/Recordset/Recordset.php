<?php

declare(strict_types=1);

namespace Velm\Recordset;

use Velm\Domain\Domain;
use Velm\Environment;
use Velm\Fields\Field;
use Velm\Models\Model;
use Velm\Registry;

final class Recordset
{
    /**
     * @param  class-string<Model>  $modelClass
     * @param  list<int>  $ids
     */
    public function __construct(
        private readonly Environment $env,
        private readonly string $modelClass,
        private array $ids,
    ) {}

    public function modelName(): string
    {
        return $this->modelClass::name();
    }

    /**
     * @return list<int>
     */
    public function ids(): array
    {
        return $this->ids;
    }

    public function count(): int
    {
        return count($this->ids);
    }

    public function ensureOne(): void
    {
        if ($this->count() !== 1) {
            throw new \InvalidArgumentException(
                'Expected a single record on '.$this->modelName().', got '.$this->count().'.',
            );
        }
    }

    /**
     * Dispatch a public instance method on the model MRO (effective class first).
     *
     * @param  list<mixed>  $arguments
     */
    public function __call(string $name, array $arguments): mixed
    {
        $implementor = $this->resolveRecordMethodClass($name);

        if ($implementor === null) {
            throw new \BadMethodCallException(
                "Call to undefined method {$this->modelName()}::{$name}().",
            );
        }

        return Registry::with(
            $this->env->registry,
            fn (): mixed => $implementor::behavior()->{$name}($this, ...$arguments),
        );
    }

    /**
     * @return class-string<Model>|null
     */
    private function resolveRecordMethodClass(string $name): ?string
    {
        $chain = $this->env->registry->extensionChainFor($this->modelName());

        for ($index = count($chain) - 1; $index >= 0; $index--) {
            $class = $chain[$index];

            if ($class::isRecordMethod($name)) {
                return $class;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function create(array $values): self
    {
        $modelClass = $this->modelClass;
        $fields = $this->modelFields();
        $columns = [];
        $placeholders = [];
        $params = [];

        foreach ($values as $name => $value) {
            if (! isset($fields[$name]) || $name === 'id' || $name === 'display_name') {
                throw new \InvalidArgumentException("Unknown field {$name} on {$modelClass::name()}.");
            }

            $field = $fields[$name];
            $columns[] = '"'.$field->column.'"';
            $placeholders[] = '?';
            $params[] = $field->toSql($value);
        }

        foreach ($fields as $name => $field) {
            if ($name === 'id' || $name === 'display_name' || array_key_exists($name, $values)) {
                continue;
            }

            if ($field->default === null) {
                continue;
            }

            $columns[] = '"'.$field->column.'"';
            $placeholders[] = '?';
            $params[] = $field->toSql($field->default);
        }

        if ($columns === []) {
            $this->env->connection->execute(
                'INSERT INTO "'.$modelClass::table().'" DEFAULT VALUES',
            );
        } else {
            $sql = 'INSERT INTO "'.$modelClass::table().'" ('.implode(', ', $columns).') VALUES ('.implode(', ', $placeholders).')';
            $this->env->connection->execute($sql, $params);
        }

        $id = $this->env->connection->lastInsertId();
        $this->env->cache->forget($modelClass::name(), $id);

        return new self($this->env, $modelClass, [$id]);
    }

    /**
     * @param  list<string>|null  $fieldNames
     * @return list<array<string, mixed>>
     */
    public function read(?array $fieldNames = null): array
    {
        if ($this->ids === []) {
            return [];
        }

        $modelClass = $this->modelClass;
        $fields = $this->modelFields();
        $fieldNames ??= array_keys(array_filter(
            $fields,
            static fn (Field $field, string $name): bool => $name !== 'display_name',
            ARRAY_FILTER_USE_BOTH,
        ));
        $fieldNames[] = 'display_name';
        $fieldNames = array_values(array_unique($fieldNames));

        $placeholders = implode(', ', array_fill(0, count($this->ids), '?'));
        $sql = 'SELECT "id"';
        foreach ($fieldNames as $name) {
            if ($name === 'id' || $name === 'display_name') {
                continue;
            }
            if (! isset($fields[$name])) {
                continue;
            }
            $sql .= ', "'.$fields[$name]->column.'"';
        }
        $sql .= ' FROM "'.$modelClass::table().'" WHERE "id" IN ('.$placeholders.')';

        $rows = $this->env->connection->fetchAll($sql, $this->ids);
        $result = [];

        foreach ($rows as $row) {
            $record = ['id' => (int) $row['id']];
            foreach ($fieldNames as $name) {
                if ($name === 'id' || $name === 'display_name') {
                    continue;
                }
                $field = $fields[$name];
                $record[$name] = $field->toPhp($row[$field->column] ?? null);
            }
            $record['display_name'] = Registry::with(
                $this->env->registry,
                function () use ($modelClass, $record): string {
                    $displayClass = Model::resolveStaticHookClass(
                        $this->env->registry,
                        $modelClass::name(),
                        'displayNameFor',
                    ) ?? Model::class;

                    return $displayClass::displayNameFor($record);
                },
            );
            $result[] = $record;

            foreach ($record as $fname => $fvalue) {
                $this->env->cache->set($modelClass::name(), $record['id'], $fname, $fvalue);
            }
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function write(array $values): void
    {
        if ($this->ids === []) {
            return;
        }

        $modelClass = $this->modelClass;
        $fields = $this->modelFields();
        $sets = [];
        $params = [];

        foreach ($values as $name => $value) {
            if ($name === 'id' || $name === 'display_name') {
                continue;
            }
            if (! isset($fields[$name])) {
                throw new \InvalidArgumentException("Unknown field {$name}.");
            }
            $field = $fields[$name];
            $sets[] = '"'.$field->column.'" = ?';
            $params[] = $field->toSql($value);
        }

        if ($sets === []) {
            return;
        }

        $placeholders = implode(', ', array_fill(0, count($this->ids), '?'));
        $sql = 'UPDATE "'.$modelClass::table().'" SET '.implode(', ', $sets).' WHERE "id" IN ('.$placeholders.')';
        $params = [...$params, ...$this->ids];
        $this->env->connection->execute($sql, $params);

        foreach ($this->ids as $id) {
            $this->env->cache->forget($modelClass::name(), $id);
        }
    }

    public function unlink(): void
    {
        if ($this->ids === []) {
            return;
        }

        $modelClass = $this->modelClass;
        $placeholders = implode(', ', array_fill(0, count($this->ids), '?'));
        $sql = 'DELETE FROM "'.$modelClass::table().'" WHERE "id" IN ('.$placeholders.')';
        $this->env->connection->execute($sql, $this->ids);

        foreach ($this->ids as $id) {
            $this->env->cache->forget($modelClass::name(), $id);
        }
    }

    /**
     * @param  list<mixed>|list<list<mixed>>  $domain
     */
    public function search(array $domain = [], int $limit = 0, int $offset = 0, ?string $order = null): self
    {
        $modelClass = $this->modelClass;
        $sql = 'SELECT "id" FROM "'.$modelClass::table().'"';
        $params = [];
        $clauses = $this->buildWhere($domain, $params);

        if ($clauses !== '') {
            $sql .= ' WHERE '.$clauses;
        }

        $sql .= ' ORDER BY '.($order ?? '"id"');

        if ($limit > 0) {
            $sql .= ' LIMIT '.$limit;
        }

        if ($offset > 0) {
            $sql .= ' OFFSET '.$offset;
        }

        $rows = $this->env->connection->fetchAll($sql, $params);
        $ids = array_map(static fn (array $row): int => (int) $row['id'], $rows);

        return new self($this->env, $modelClass, $ids);
    }

    /**
     * @param  list<mixed>|list<list<mixed>>  $domain
     * @param  list<mixed>  $params
     */
    private function buildWhere(array $domain, array &$params): string
    {
        $parts = [];

        foreach ($this->expandDomain($domain) as $leaf) {
            if ($leaf[0] === '__or__') {
                $subParts = [];
                $subs = is_array($leaf[2]) ? $leaf[2] : [];

                foreach ($subs as $sub) {
                    if (! is_array($sub) || count($sub) !== 3) {
                        continue;
                    }

                    $subParts[] = $this->buildLeafClause(Domain::fromArray($sub), $params);
                }

                if ($subParts !== []) {
                    $parts[] = '('.implode(' OR ', $subParts).')';
                }

                continue;
            }

            $parts[] = $this->buildLeafClause(Domain::fromArray($leaf), $params);
        }

        return implode(' AND ', $parts);
    }

    /**
     * @param  list<mixed>|list<list<mixed>>  $domain
     * @return list<list<mixed>>
     */
    private function expandDomain(array $domain): array
    {
        if ($domain === []) {
            return [];
        }

        if (! is_array($domain[0])) {
            return [$domain];
        }

        /** @var list<list<mixed>> $domain */
        return $domain;
    }

    /**
     * @return array<string, Field>
     */
    private function modelFields(): array
    {
        $name = $this->modelClass::name();

        if ($this->env->registry->hasFieldSet($name)) {
            return $this->env->registry->fieldSet($name);
        }

        return $this->modelClass::fields();
    }

    private function buildLeafClause(Domain $leaf, array &$params): string
    {
        $field = $this->modelFields()[$leaf->field] ?? null;

        if ($field === null) {
            throw new \InvalidArgumentException("Unknown domain field {$leaf->field}.");
        }

        $sql = match ($leaf->operator) {
            '=' => '"'.$field->column.'" = ?',
            '!=' => '"'.$field->column.'" <> ?',
            '>' => '"'.$field->column.'" > ?',
            '<' => '"'.$field->column.'" < ?',
            '>=' => '"'.$field->column.'" >= ?',
            '<=' => '"'.$field->column.'" <= ?',
            'like', 'ilike' => 'LOWER(CAST("'.$field->column.'" AS TEXT)) LIKE LOWER(?)',
            default => throw new \InvalidArgumentException("Unsupported operator {$leaf->operator}."),
        };
        $params[] = $field->toSql($leaf->value);

        return $sql;
    }
}
