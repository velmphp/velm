<?php

declare(strict_types=1);

namespace Velm\Recordset;

use Velm\Database\SqlQuote;
use Velm\Domain\Domain;
use Velm\Domain\DomainCompiler;
use Velm\Environment;
use Velm\Exception\AccessDeniedException;
use Velm\Fields\DatetimeField;
use Velm\Fields\Field;
use Velm\Fields\Many2manyField;
use Velm\Fields\One2manyField;
use Velm\Support\VelmDatetime;
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

    private function q(string $identifier): string
    {
        return SqlQuote::identifier($this->env->connection, $identifier);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function create(array $values): self
    {
        $this->env->checkAccess($this->modelName(), 'create');

        $values = $this->env->enforceCompanyOnCreate($this->modelName(), $values);
        $values = $this->applyPrepareValues($values, 'create');
        $values = $this->applyTimestamps($values, 'create');

        $modelClass = $this->modelClass;
        [$columnValues, $m2mValues, $o2mValues] = $this->splitValues($values);
        $fields = $this->modelFields();
        $columns = [];
        $placeholders = [];
        $params = [];

        foreach ($columnValues as $name => $value) {
            $field = $fields[$name];
            $columns[] = $this->q($field->column);
            $placeholders[] = '?';
            $params[] = $field->toSql($this->datetimeToStorage($value, $field));
        }

        foreach ($fields as $name => $field) {
            if ($name === 'id' || $name === 'display_name' || $field instanceof Many2manyField || $field instanceof One2manyField || array_key_exists($name, $columnValues)) {
                continue;
            }

            if ($field->default === null) {
                continue;
            }

            $columns[] = $this->q($field->column);
            $placeholders[] = '?';
            $params[] = $field->toSql($this->datetimeToStorage($field->default, $field));
        }

        if ($columns === []) {
            $this->env->connection->execute(
                'INSERT INTO '.$this->q($modelClass::table()).' DEFAULT VALUES',
            );
        } else {
            $sql = 'INSERT INTO '.$this->q($modelClass::table()).' ('.implode(', ', $columns).') VALUES ('.implode(', ', $placeholders).')';
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

        $this->env->computeRunner()->computeStoredOnCreate($created);

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

        $this->assertAllIdsInCompanyScope();

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
        $sql = 'SELECT '.$this->q('id');
        foreach ($fieldNames as $name) {
            if ($name === 'id' || $name === 'display_name') {
                continue;
            }
            $field = $fields[$name] ?? null;

            if ($field === null || $field instanceof Many2manyField || $field instanceof One2manyField || ! $field->persistsInDatabase()) {
                continue;
            }

            $sql .= ', '.$this->q($field->column);
        }
        $sql .= ' FROM '.$this->q($modelClass::table()).' WHERE '.$this->q('id').' IN ('.$placeholders.')';

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

                if ($field->isComputed() && ! $field->isStored()) {
                    continue;
                }

                $record[$name] = $this->datetimeFromStorage($row[$field->column] ?? null, $field);
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

        $this->env->computeRunner()->fillUnstoredForRead($this, $fieldNames);

        foreach ($result as &$record) {
            foreach ($fieldNames as $name) {
                $field = $fields[$name] ?? null;

                if ($field === null || ! $field->isComputed() || $field->isStored()) {
                    continue;
                }

                $record[$name] = $this->env->cache->get($modelClass::name(), $record['id'], $name);
            }
        }
        unset($record);

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

        $this->assertAllIdsInCompanyScope();

        $this->env->checkAccess($this->modelName(), 'write');

        $values = $this->env->enforceCompanyOnWrite($this->modelName(), $values);
        $values = $this->applyPrepareValues($values, 'write');
        $values = $this->applyTimestamps($values, 'write');

        [$columnValues, $m2mValues, $o2mValues] = $this->splitValues($values);
        $modelClass = $this->modelClass;
        $fields = $this->modelFields();
        $sets = [];
        $params = [];

        foreach ($columnValues as $name => $value) {
            $field = $fields[$name];
            $sets[] = $this->q($field->column).' = ?';
            $params[] = $field->toSql($this->datetimeToStorage($value, $field));
        }

        if ($sets !== []) {
            $placeholders = implode(', ', array_fill(0, count($this->ids), '?'));
            $sql = 'UPDATE '.$this->q($modelClass::table()).' SET '.implode(', ', $sets).' WHERE '.$this->q('id').' IN ('.$placeholders.')';
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

        $this->env->computeRunner()->recomputeAfterWrite($this, array_keys($columnValues));
    }

    public function unlink(): void
    {
        if ($this->ids === []) {
            return;
        }

        $implementor = $this->resolveRecordMethodClass('unlink');

        if ($implementor !== null) {
            Registry::with(
                $this->env->registry,
                fn (): mixed => $implementor::behavior()->unlink($this),
            );

            return;
        }

        $this->unlinkFromDatabase();
    }

    public function unlinkFromDatabase(): void
    {
        if ($this->ids === []) {
            return;
        }

        $this->assertAllIdsInCompanyScope();

        $this->env->checkAccess($this->modelName(), 'unlink');

        $modelClass = $this->modelClass;
        $placeholders = implode(', ', array_fill(0, count($this->ids), '?'));
        $sql = 'DELETE FROM '.$this->q($modelClass::table()).' WHERE '.$this->q('id').' IN ('.$placeholders.')';
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
        $sql = 'SELECT '.$this->q('id').' FROM '.$this->q($modelClass::table());
        $params = [];
        $clauses = $this->buildWhere($domain, $params);

        if ($clauses !== '') {
            $sql .= ' WHERE '.$clauses;
        }

        $sql .= ' ORDER BY '.($order ?? $this->q('id'));

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
     * @param  list<string>  $fields  Aggregate specs, e.g. {@code amount:sum}
     * @param  list<string>  $groupby  e.g. {@code state}, {@code created_at:month}
     * @return list<array<string, mixed>>
     */
    public function readGroup(
        array $domain = [],
        array $fields = [],
        array $groupby = [],
        int $offset = 0,
        int $limit = 0,
        ?string $orderby = null,
    ): array {
        $this->env->checkAccess($this->modelName(), 'read');

        $scopedDomain = $this->collectSearchDomain($domain, 'read');

        return (new ReadGroupQuery($this->env, $this->modelClass))->run(
            $scopedDomain,
            $fields,
            $groupby,
            fn (array $whereDomain, array &$whereParams): string => $this->buildWhere($whereDomain, $whereParams),
            $offset,
            $limit,
            $orderby,
        );
    }

    /**
     * @param  list<mixed>|list<list<mixed>>  $userDomain
     * @return list<mixed>|list<list<mixed>>
     */
    private function collectSearchDomain(array $userDomain, string $perm): array
    {
        $domain = $userDomain;

        foreach ($this->env->companySearchConstraints($this->modelName()) as $leaf) {
            $domain[] = $leaf;
        }

        foreach ($this->env->collectRecordRules($this->modelName(), $perm) as $leaf) {
            $domain[] = $leaf;
        }

        return $domain;
    }

    private function assertAllIdsInCompanyScope(): void
    {
        if ($this->env->companySearchConstraints($this->modelName()) === []) {
            return;
        }

        $scoped = $this->env->model($this->modelName())
            ->search([['id', 'in', $this->ids]])
            ->ids();

        if (count($scoped) !== count($this->ids)) {
            throw AccessDeniedException::forCompanyScope($this->modelName(), $this->env->uid);
        }
    }

    /**
     * @param  list<mixed>|list<list<mixed>>  $domain
     * @param  list<mixed>  $params
     */
    private function buildWhere(array $domain, array &$params): string
    {
        return (new DomainCompiler)->compileWhere(
            $domain,
            function (Domain $leaf) use (&$params): string {
                return $this->buildLeafClause($leaf, $params);
            },
            $params,
        );
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
                '=' => $this->q($field->column).' IS NULL',
                '!=' => $this->q($field->column).' IS NOT NULL',
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

            return $this->q($field->column).' IN ('.$holders.')';
        }

        $column = $this->q($field->column);
        $sql = match ($leaf->operator) {
            '=' => $column.' = ?',
            '!=' => $column.' <> ?',
            '>' => $column.' > ?',
            '<' => $column.' < ?',
            '>=' => $column.' >= ?',
            '<=' => $column.' <= ?',
            'like', 'ilike' => $this->likeClause($column),
            default => throw new \InvalidArgumentException("Unsupported operator {$leaf->operator}."),
        };
        $params[] = $field->toSql($leaf->value);

        return $sql;
    }

    private function likeClause(string $column): string
    {
        return match ($this->env->connection->driver()) {
            'pgsql' => $column.' ILIKE ?',
            'mysql' => 'LOWER(CAST('.$column.' AS CHAR)) LIKE LOWER(?)',
            default => 'LOWER(CAST('.$column.' AS TEXT)) LIKE LOWER(?)',
        };
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

            if ($field->isComputed()) {
                throw new \InvalidArgumentException(
                    "Cannot write computed field {$name} on {$this->modelName()}; update its dependencies instead.",
                );
            }

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
                'DELETE FROM '.$this->q($relation).' WHERE '.$this->q($col1).' IN ('.$ownerPlaceholders.')',
                $this->ids,
            );

            foreach ($this->ids as $ownerId) {
                foreach ($peerIds as $peerId) {
                    $this->env->connection->execute(
                        'INSERT INTO '.$this->q($relation).' ('.$this->q($col1).', '.$this->q($col2).') VALUES (?, ?)',
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
                'SELECT '.$this->q($col1).' AS owner_id, '.$this->q($col2).' AS peer_id FROM '.$this->q($relation).' '
                .'WHERE '.$this->q($col1).' IN ('.$ownerPlaceholders.') ORDER BY '.$this->q($col2),
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
                'UPDATE '.$this->q($table).' SET '.$this->q($inverseColumn).' = NULL WHERE '.$this->q($inverseColumn).' = ?'
                .($childIds !== [] ? ' AND '.$this->q('id').' NOT IN ('.$placeholders.')' : ''),
                $childIds !== [] ? [$parentId, ...$childIds] : [$parentId],
            );

            if ($childIds === []) {
                continue;
            }

            $linkPlaceholders = implode(', ', array_fill(0, count($childIds), '?'));
            $this->env->connection->execute(
                'UPDATE '.$this->q($table).' SET '.$this->q($inverseColumn).' = ? WHERE '.$this->q('id').' IN ('.$linkPlaceholders.')',
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
                'SELECT '.$this->q('id').', '.$this->q($inverseColumn).' AS owner_id FROM '.$this->q($table).' '
                .'WHERE '.$this->q($inverseColumn).' IN ('.$ownerPlaceholders.') ORDER BY '.$this->q('id'),
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

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function applyPrepareValues(array $values, string $operation): array
    {
        $hook = Model::resolveStaticHookClass(
            $this->env->registry,
            $this->modelName(),
            'prepareValues',
        );

        if ($hook === null) {
            return $values;
        }

        return $hook::prepareValues($values, $operation);
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function applyTimestamps(array $values, string $operation): array
    {
        if (! $this->modelClass::usesTimestamps()) {
            return $values;
        }

        $fields = $this->modelFields();
        $now = VelmDatetime::fromUtc(VelmDatetime::nowUtc(), $this->env->timezone()) ?? VelmDatetime::nowUtc();

        if ($operation === 'create') {
            if (isset($fields['created_at']) && ($values['created_at'] ?? null) === null) {
                $values['created_at'] = $now;
            }

            if (isset($fields['updated_at']) && ($values['updated_at'] ?? null) === null) {
                $values['updated_at'] = $now;
            }
        }

        if ($operation === 'write') {
            unset($values['created_at']);

            if (isset($fields['updated_at'])) {
                $values['updated_at'] = $now;
            }
        }

        return $values;
    }

    private function datetimeToStorage(mixed $value, Field $field): mixed
    {
        if (! $field instanceof DatetimeField) {
            return $value;
        }

        return VelmDatetime::toUtc($value, $this->env->timezone());
    }

    private function datetimeFromStorage(mixed $value, Field $field): mixed
    {
        if (! $field instanceof DatetimeField) {
            return $field->toPhp($value);
        }

        return VelmDatetime::fromUtc($field->toPhp($value), $this->env->timezone());
    }
}
