<?php

declare(strict_types=1);

namespace Velm\Recordset;

use Velm\Domain\Domain;
use Velm\Environment;
use Velm\Fields\Field;
use Velm\Fields\Many2manyField;
use Velm\Fields\One2manyField;
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
        $this->env->checkAccess($this->modelName(), 'create');

        $modelClass = $this->modelClass;
        [$columnValues, $m2mValues, $o2mValues] = $this->splitValues($values);
        $fields = $this->modelFields();
        $columns = [];
        $placeholders = [];
        $params = [];

        foreach ($columnValues as $name => $value) {
            $field = $fields[$name];
            $columns[] = '"'.$field->column.'"';
            $placeholders[] = '?';
            $params[] = $field->toSql($value);
        }

        foreach ($fields as $name => $field) {
            if ($name === 'id' || $name === 'display_name' || $field instanceof Many2manyField || $field instanceof One2manyField || array_key_exists($name, $columnValues)) {
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

        $created = new self($this->env, $modelClass, [$id]);

        if ($m2mValues !== []) {
            $created->writeM2m($m2mValues);
        }

        if ($o2mValues !== []) {
            $created->writeO2m($o2mValues);
        }

        return $created;
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

        $this->env->checkAccess($this->modelName(), 'read');

        $modelClass = $this->modelClass;
        $fields = $this->modelFields();
        $fieldNames ??= array_keys(array_filter(
            $fields,
            static fn (Field $field, string $name): bool => $name !== 'display_name',
            ARRAY_FILTER_USE_BOTH,
        ));
        $fieldNames[] = 'display_name';
        $fieldNames = array_values(array_unique($fieldNames));

        $m2mNames = [];
        $o2mNames = [];
        foreach ($fieldNames as $name) {
            $field = $fields[$name] ?? null;

            if ($field instanceof Many2manyField) {
                $m2mNames[] = $name;
            }

            if ($field instanceof One2manyField) {
                $o2mNames[] = $name;
            }
        }

        $placeholders = implode(', ', array_fill(0, count($this->ids), '?'));
        $sql = 'SELECT "id"';
        foreach ($fieldNames as $name) {
            if ($name === 'id' || $name === 'display_name') {
                continue;
            }
            if (! isset($fields[$name]) || $fields[$name] instanceof Many2manyField || $fields[$name] instanceof One2manyField) {
                continue;
            }
            $sql .= ', "'.$fields[$name]->column.'"';
        }
        $sql .= ' FROM "'.$modelClass::table().'" WHERE "id" IN ('.$placeholders.')';

        $rows = $this->env->connection->fetchAll($sql, $this->ids);
        $m2mByRecord = $m2mNames !== [] ? $this->loadM2mMaps($m2mNames) : [];
        $o2mByRecord = $o2mNames !== [] ? $this->loadO2mMaps($o2mNames) : [];
        $result = [];

        foreach ($rows as $row) {
            $record = ['id' => (int) $row['id']];
            foreach ($fieldNames as $name) {
                if ($name === 'id' || $name === 'display_name') {
                    continue;
                }
                $field = $fields[$name];

                if ($field instanceof Many2manyField) {
                    $record[$name] = $m2mByRecord[$name][$record['id']] ?? [];

                    continue;
                }

                if ($field instanceof One2manyField) {
                    $record[$name] = $o2mByRecord[$name][$record['id']] ?? [];

                    continue;
                }

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

        $this->env->checkAccess($this->modelName(), 'write');

        [$columnValues, $m2mValues, $o2mValues] = $this->splitValues($values);
        $modelClass = $this->modelClass;
        $fields = $this->modelFields();
        $sets = [];
        $params = [];

        foreach ($columnValues as $name => $value) {
            $field = $fields[$name];
            $sets[] = '"'.$field->column.'" = ?';
            $params[] = $field->toSql($value);
        }

        if ($sets !== []) {
            $placeholders = implode(', ', array_fill(0, count($this->ids), '?'));
            $sql = 'UPDATE "'.$modelClass::table().'" SET '.implode(', ', $sets).' WHERE "id" IN ('.$placeholders.')';
            $params = [...$params, ...$this->ids];
            $this->env->connection->execute($sql, $params);
        }

        if ($m2mValues !== []) {
            $this->writeM2m($m2mValues);
        }

        if ($o2mValues !== []) {
            $this->writeO2m($o2mValues);
        }

        if ($sets !== [] || $m2mValues !== [] || $o2mValues !== []) {
            foreach ($this->ids as $id) {
                $this->env->cache->forget($modelClass::name(), $id);
            }
        }
    }

    public function unlink(): void
    {
        if ($this->ids === []) {
            return;
        }

        $this->env->checkAccess($this->modelName(), 'unlink');

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
        $this->env->checkAccess($this->modelName(), 'read');

        $domain = $this->collectSearchDomain($domain, 'read');

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
     * @param  list<mixed>|list<list<mixed>>  $userDomain
     * @return list<mixed>|list<list<mixed>>
     */
    private function collectSearchDomain(array $userDomain, string $perm): array
    {
        $domain = $userDomain;

        foreach ($this->env->collectRecordRules($this->modelName(), $perm) as $leaf) {
            $domain[] = $leaf;
        }

        return $domain;
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

        if ($leaf->value === null || $leaf->value === false) {
            return match ($leaf->operator) {
                '=' => '"'.$field->column.'" IS NULL',
                '!=' => '"'.$field->column.'" IS NOT NULL',
                default => throw new \InvalidArgumentException("Unsupported operator {$leaf->operator} for null."),
            };
        }

        if ($leaf->operator === 'in') {
            $values = is_array($leaf->value) ? $leaf->value : [$leaf->value];

            if ($values === []) {
                return '0 = 1';
            }

            $holders = implode(', ', array_fill(0, count($values), '?'));

            foreach ($values as $value) {
                $params[] = $field->toSql($value);
            }

            return '"'.$field->column.'" IN ('.$holders.')';
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

    /**
     * @param  array<string, mixed>  $values
     * @return array{0: array<string, mixed>, 1: array<string, list<int>>, 2: array<string, list<int>>}
     */
    private function splitValues(array $values): array
    {
        $fields = $this->modelFields();
        $columnValues = [];
        $m2mValues = [];
        $o2mValues = [];

        foreach ($values as $name => $value) {
            if ($name === 'id' || $name === 'display_name') {
                continue;
            }

            if (! isset($fields[$name])) {
                throw new \InvalidArgumentException("Unknown field {$name} on {$this->modelName()}.");
            }

            $field = $fields[$name];

            if ($field instanceof Many2manyField) {
                $m2mValues[$name] = $this->normalizeRelationIds($value);

                continue;
            }

            if ($field instanceof One2manyField) {
                $o2mValues[$name] = $this->normalizeRelationIds($value);

                continue;
            }

            $columnValues[$name] = $value;
        }

        return [$columnValues, $m2mValues, $o2mValues];
    }

    /**
     * @return list<int>
     */
    private function normalizeRelationIds(mixed $value): array
    {
        if ($value instanceof self) {
            return $value->ids();
        }

        if (! is_array($value)) {
            throw new \InvalidArgumentException('Relation field values must be a list of ids or a recordset.');
        }

        return array_values(array_map(intval(...), $value));
    }

    /**
     * @param  array<string, list<int>>  $m2mValues
     */
    private function writeM2m(array $m2mValues): void
    {
        $fields = $this->modelFields();

        foreach ($m2mValues as $name => $peerIds) {
            $field = $fields[$name];

            if (! $field instanceof Many2manyField) {
                continue;
            }

            [$relation, $col1, $col2] = $field->resolveSpec($this->modelClass, $this->env->registry);
            $ownerPlaceholders = implode(', ', array_fill(0, count($this->ids), '?'));
            $this->env->connection->execute(
                'DELETE FROM "'.$relation.'" WHERE "'.$col1.'" IN ('.$ownerPlaceholders.')',
                $this->ids,
            );

            foreach ($this->ids as $ownerId) {
                foreach ($peerIds as $peerId) {
                    $this->env->connection->execute(
                        'INSERT INTO "'.$relation.'" ("'.$col1.'", "'.$col2.'") VALUES (?, ?)',
                        [$ownerId, $peerId],
                    );
                }
            }
        }
    }

    /**
     * @param  list<string>  $m2mNames
     * @return array<string, array<int, list<int>>>
     */
    private function loadM2mMaps(array $m2mNames): array
    {
        $fields = $this->modelFields();
        $maps = [];

        foreach ($m2mNames as $name) {
            $field = $fields[$name];

            if (! $field instanceof Many2manyField) {
                continue;
            }

            [$relation, $col1, $col2] = $field->resolveSpec($this->modelClass, $this->env->registry);
            $ownerPlaceholders = implode(', ', array_fill(0, count($this->ids), '?'));
            $rows = $this->env->connection->fetchAll(
                'SELECT "'.$col1.'" AS owner_id, "'.$col2.'" AS peer_id FROM "'.$relation.'" '
                .'WHERE "'.$col1.'" IN ('.$ownerPlaceholders.') ORDER BY "'.$col2.'"',
                $this->ids,
            );
            $map = [];

            foreach ($this->ids as $id) {
                $map[$id] = [];
            }

            foreach ($rows as $row) {
                $ownerId = (int) $row['owner_id'];
                $map[$ownerId][] = (int) $row['peer_id'];
            }

            $maps[$name] = $map;
        }

        return $maps;
    }

    /**
     * @param  array<string, list<int>>  $o2mValues
     */
    private function writeO2m(array $o2mValues): void
    {
        if (count($this->ids) !== 1) {
            throw new \InvalidArgumentException('One2many writes require a single parent record.');
        }

        $parentId = $this->ids[0];
        $fields = $this->modelFields();

        foreach ($o2mValues as $name => $childIds) {
            $field = $fields[$name];

            if (! $field instanceof One2manyField) {
                continue;
            }

            $comodelClass = $this->env->registry->modelClass($field->comodel);
            $inverse = $comodelClass::fields()[$field->inverseName];
            $inverseColumn = $inverse->column;
            $table = $comodelClass::table();
            $placeholders = implode(', ', array_fill(0, count($childIds), '?'));

            $this->env->connection->execute(
                'UPDATE "'.$table.'" SET "'.$inverseColumn.'" = NULL WHERE "'.$inverseColumn.'" = ?'
                .($childIds !== [] ? ' AND "id" NOT IN ('.$placeholders.')' : ''),
                $childIds !== [] ? [$parentId, ...$childIds] : [$parentId],
            );

            if ($childIds === []) {
                continue;
            }

            $linkPlaceholders = implode(', ', array_fill(0, count($childIds), '?'));
            $this->env->connection->execute(
                'UPDATE "'.$table.'" SET "'.$inverseColumn.'" = ? WHERE "id" IN ('.$linkPlaceholders.')',
                [$parentId, ...$childIds],
            );
        }
    }

    /**
     * @param  list<string>  $o2mNames
     * @return array<string, array<int, list<int>>>
     */
    private function loadO2mMaps(array $o2mNames): array
    {
        $fields = $this->modelFields();
        $maps = [];

        foreach ($o2mNames as $name) {
            $field = $fields[$name];

            if (! $field instanceof One2manyField) {
                continue;
            }

            $comodelClass = $this->env->registry->modelClass($field->comodel);
            $inverse = $comodelClass::fields()[$field->inverseName];
            $inverseColumn = $inverse->column;
            $table = $comodelClass::table();
            $ownerPlaceholders = implode(', ', array_fill(0, count($this->ids), '?'));
            $rows = $this->env->connection->fetchAll(
                'SELECT "id", "'.$inverseColumn.'" AS owner_id FROM "'.$table.'" '
                .'WHERE "'.$inverseColumn.'" IN ('.$ownerPlaceholders.') ORDER BY "id"',
                $this->ids,
            );
            $map = [];

            foreach ($this->ids as $id) {
                $map[$id] = [];
            }

            foreach ($rows as $row) {
                $ownerId = (int) $row['owner_id'];
                $map[$ownerId][] = (int) $row['id'];
            }

            $maps[$name] = $map;
        }

        return $maps;
    }
}
