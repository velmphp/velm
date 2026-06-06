<?php

declare(strict_types=1);

namespace Velm\Recordset;

use Velm\Database\SqlQuote;
use Velm\Environment;
use Velm\Fields\BooleanField;
use Velm\Fields\DatetimeField;
use Velm\Fields\Field;
use Velm\Fields\Many2oneField;
use Velm\Models\Model;

/**
 * Builds grouped aggregate queries for {@see Recordset::readGroup()}.
 */
final class ReadGroupQuery
{
    /** @var array<string, Field> */
    private array $fields;

    /**
     * @param  class-string<Model>  $modelClass
     */
    public function __construct(
        private readonly Environment $env,
        private readonly string $modelClass,
    ) {
        $this->fields = $this->resolveFields();
    }

    /**
     * @param  list<mixed>|list<list<mixed>>  $domain
     * @param  list<string>  $fields  Aggregate specs, e.g. {@code amount:sum}
     * @param  list<string>  $groupby  e.g. {@code state}, {@code created_at:month}
     * @return list<array<string, mixed>>
     */
    public function run(
        array $domain,
        array $fields,
        array $groupby,
        callable $whereBuilder,
        int $offset = 0,
        int $limit = 0,
        ?string $orderby = null,
    ): array {
        if ($groupby === []) {
            throw new \InvalidArgumentException('readGroup() requires at least one groupby field.');
        }

        $params = [];
        $select = [];
        $groupExpressions = [];
        $groupAliases = [];

        foreach ($groupby as $spec) {
            [$name, $granularity] = $this->parseSpec($spec);
            $field = $this->requireField($name);
            $expression = $this->groupExpression($field, $granularity);
            $alias = $this->groupAlias($name, $granularity);
            $select[] = $expression.' AS '.$this->q($alias);
            $groupExpressions[] = $expression;
            $groupAliases[] = $alias;
        }

        $select[] = 'COUNT(*) AS '.$this->q('__count');

        foreach ($fields as $spec) {
            [$name, $aggregate] = $this->parseAggregate($spec);
            $field = $this->requireField($name);
            $alias = $name.'_'.$aggregate;
            $select[] = $this->aggregateExpression($field, $aggregate).' AS '.$this->q($alias);
        }

        $sql = 'SELECT '.implode(', ', $select)
            .' FROM '.$this->q($this->modelClass::table());

        $where = $whereBuilder($domain, $params);

        if ($where !== '') {
            $sql .= ' WHERE '.$where;
        }

        $sql .= ' GROUP BY '.implode(', ', $groupExpressions);

        if ($orderby !== null && $orderby !== '') {
            $sql .= ' ORDER BY '.$this->orderExpression($orderby);
        } else {
            $sql .= ' ORDER BY '.$this->q($groupAliases[0]);
        }

        if ($limit > 0) {
            $sql .= ' LIMIT '.$limit;
        }

        if ($offset > 0) {
            $sql .= ' OFFSET '.$offset;
        }

        $rows = $this->env->connection->fetchAll($sql, $params);
        $result = [];

        foreach ($rows as $row) {
            $entry = ['__count' => (int) $row['__count']];
            $domainLeaves = [];

            foreach ($groupby as $index => $spec) {
                [$name, $granularity] = $this->parseSpec($spec);
                $alias = $groupAliases[$index];
                $field = $this->requireField($name);
                $raw = $row[$alias] ?? null;
                $value = $this->groupValue($field, $granularity, $raw);
                $entry[$name] = $value;
                $entry[$name.'_count'] = $entry['__count'];
                $domainLeaves = [...$domainLeaves, ...$this->groupDomainLeaves($name, $field, $granularity, $raw)];
            }

            foreach ($fields as $spec) {
                [$name, $aggregate] = $this->parseAggregate($spec);
                $alias = $name.'_'.$aggregate;
                $entry[$alias] = $this->aggregateValue($this->requireField($name), $aggregate, $row[$alias] ?? null);
            }

            $entry['__domain'] = $domainLeaves;

            $result[] = $entry;
        }

        return $result;
    }

    /**
     * @return array{0: string, 1: string|null}
     */
    private function parseSpec(string $spec): array
    {
        if (str_contains($spec, ':')) {
            [$name, $granularity] = explode(':', $spec, 2);

            return [$name, $granularity !== '' ? $granularity : null];
        }

        return [$spec, null];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function parseAggregate(string $spec): array
    {
        if (! str_contains($spec, ':')) {
            throw new \InvalidArgumentException("Aggregate field {$spec} must use field:aggregate syntax.");
        }

        [$name, $aggregate] = explode(':', $spec, 2);
        $aggregate = strtolower($aggregate);

        if (! in_array($aggregate, ['count', 'sum', 'avg', 'min', 'max'], true)) {
            throw new \InvalidArgumentException("Unsupported aggregate {$aggregate} on {$name}.");
        }

        return [$name, $aggregate];
    }

    private function requireField(string $name): Field
    {
        $field = $this->fields[$name] ?? null;

        if ($field === null) {
            throw new \InvalidArgumentException("Unknown group field {$name} on {$this->modelClass::name()}.");
        }

        if ($field instanceof Many2oneField || $field instanceof BooleanField || $field instanceof DatetimeField || $field->persistsInDatabase()) {
            return $field;
        }

        throw new \InvalidArgumentException("Field {$name} cannot be used in readGroup().");
    }

    private function groupExpression(Field $field, ?string $granularity): string
    {
        if ($granularity === null) {
            return $this->q($field->column);
        }

        if (! $field instanceof DatetimeField) {
            throw new \InvalidArgumentException("Granularity :{$granularity} requires a datetime field.");
        }

        return match ($granularity) {
            'day' => "strftime('%Y-%m-%d', {$this->q($field->column)})",
            'month' => "strftime('%Y-%m', {$this->q($field->column)})",
            'year' => "strftime('%Y', {$this->q($field->column)})",
            default => throw new \InvalidArgumentException("Unsupported groupby granularity :{$granularity}."),
        };
    }

    private function groupAlias(string $name, ?string $granularity): string
    {
        return $granularity === null ? $name : $name.'_'.$granularity;
    }

    private function aggregateExpression(Field $field, string $aggregate): string
    {
        $column = $this->q($field->column);

        return match ($aggregate) {
            'count' => "COUNT({$column})",
            'sum' => "COALESCE(SUM({$column}), 0)",
            'avg' => "AVG({$column})",
            'min' => "MIN({$column})",
            'max' => "MAX({$column})",
        };
    }

    private function aggregateValue(Field $field, string $aggregate, mixed $raw): mixed
    {
        if ($raw === null) {
            return $aggregate === 'count' ? 0 : null;
        }

        if ($aggregate === 'avg') {
            return is_numeric($raw) ? (float) $raw : null;
        }

        if (in_array($aggregate, ['sum', 'min', 'max', 'count'], true)) {
            return (int) $raw;
        }

        return $field->toPhp($raw);
    }

    private function groupValue(Field $field, ?string $granularity, mixed $raw): mixed
    {
        if ($granularity !== null) {
            return $raw === null ? false : (string) $raw;
        }

        if ($field instanceof BooleanField) {
            return $raw === null ? false : (bool) (int) $raw;
        }

        if ($field instanceof Many2oneField) {
            return $raw === null ? false : (int) $raw;
        }

        return $field->toPhp($raw);
    }

    /**
     * @return list<list<mixed>>
     */
    private function groupDomainLeaves(string $name, Field $field, ?string $granularity, mixed $raw): array
    {
        if ($granularity !== null) {
            if ($raw === null || $raw === false || $raw === '') {
                return [[$name, '=', false]];
            }

            return [[$name, '>=', (string) $raw], [$name, '<', $this->granularityUpperBound($granularity, (string) $raw)]];
        }

        if ($field instanceof BooleanField) {
            return [[$name, '=', $raw === null ? false : (bool) (int) $raw]];
        }

        if ($field instanceof Many2oneField) {
            return [[$name, '=', $raw === null ? false : (int) $raw]];
        }

        return [[$name, '=', $field->toPhp($raw)]];
    }

    private function granularityUpperBound(string $granularity, string $value): string
    {
        return match ($granularity) {
            'day' => date('Y-m-d', strtotime($value.' +1 day')),
            'month' => date('Y-m-d', strtotime($value.'-01 +1 month')),
            'year' => date('Y-m-d', strtotime($value.'-01-01 +1 year')),
            default => throw new \InvalidArgumentException("Unsupported groupby granularity :{$granularity}."),
        };
    }

    private function orderExpression(string $orderby): string
    {
        if (str_starts_with($orderby, '-')) {
            return $this->q(substr($orderby, 1)).' DESC';
        }

        return $this->q($orderby);
    }

    private function q(string $identifier): string
    {
        return SqlQuote::identifier($this->env->connection, $identifier);
    }

    /**
     * @return array<string, Field>
     */
    private function resolveFields(): array
    {
        $name = $this->modelClass::name();

        if ($this->env->registry->hasFieldSet($name)) {
            return $this->env->registry->fieldSet($name);
        }

        return $this->modelClass::fields();
    }
}
