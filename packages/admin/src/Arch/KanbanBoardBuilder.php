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

final class KanbanBoardBuilder
{
    public function __construct(
        private readonly AnalyticsDomainBuilder $domainBuilder = new AnalyticsDomainBuilder,
        private readonly ListDomainBuilder $listDomainBuilder = new ListDomainBuilder,
        private readonly ArchSchemaBuilder $schemaBuilder = new ArchSchemaBuilder,
    ) {}

    /**
     * @param  array<string, mixed>  $arch
     * @param  array<string, mixed>  $queryArch
     * @return array{
     *     grouped: bool,
     *     group_by: string,
     *     columns: list<array{
     *         key: string,
     *         label: string,
     *         domain: list<mixed>|list<list<mixed>>,
     *         cards: list<array{
     *             id: int,
     *             title: string,
     *             subtitle: string,
     *             fields: list<array{label: string, value: string}>,
     *             badges: list<array{label: string, value: string, kind: string}>
     *         }>
     *     }>,
     *     cards: list<array{
     *         id: int,
     *         title: string,
     *         subtitle: string,
     *         fields: list<array{label: string, value: string}>,
     *         badges: list<array{label: string, value: string, kind: string}>
     *     }>
     * }
     */
    public function build(
        array $arch,
        Environment $env,
        array $queryArch,
        ListQuery $query = new ListQuery,
        ?string $groupBy = null,
    ): array {
        $arch = ArchNormalizer::normalize($arch, 'kanban');
        $model = (string) ($arch['model'] ?? '');
        $effectiveGroupBy = $groupBy ?? '';
        $card = is_array($arch['card'] ?? null) ? $arch['card'] : [];
        $domain = $this->buildDomain($arch, $queryArch, $env, $query);
        $rows = $env->model($model)->search($domain)->read();

        if ($effectiveGroupBy === '') {
            return [
                'grouped' => false,
                'group_by' => '',
                'columns' => [],
                'cards' => array_map(
                    fn (array $row): array => $this->cardPayload($model, $card, $row, $env),
                    $rows,
                ),
            ];
        }

        $columns = [];

        foreach ($rows as $row) {
            $value = $row[$effectiveGroupBy] ?? null;
            $key = $this->columnKey($value);
            $columns[$key] ??= [
                'key' => $key,
                'label' => $this->schemaBuilder->formatGroupLabel($model, $effectiveGroupBy, $value, $env),
                'domain' => $this->groupDomain($effectiveGroupBy, $value, $env, $model),
                'cards' => [],
                'sort' => $this->columnSortValue($model, $effectiveGroupBy, $value, $env),
            ];
            $columns[$key]['cards'][] = $this->cardPayload($model, $card, $row, $env);
        }

        uasort(
            $columns,
            static fn (array $left, array $right): int => $left['sort'] <=> $right['sort'],
        );

        foreach ($columns as &$column) {
            unset($column['sort']);
        }

        return [
            'grouped' => true,
            'group_by' => $effectiveGroupBy,
            'columns' => array_values($columns),
            'cards' => [],
        ];
    }

    /**
     * @param  array<string, mixed>  $arch
     * @param  array<string, mixed>  $queryArch
     * @return list<mixed>|list<list<mixed>>
     */
    private function buildDomain(array $arch, array $queryArch, Environment $env, ListQuery $query): array
    {
        $static = $this->domainBuilder->build($arch);
        $dynamic = $this->listDomainBuilder->build($queryArch, $env, $query);

        if ($static === []) {
            return $dynamic;
        }

        if ($dynamic === []) {
            return $static;
        }

        return array_merge($static, $dynamic);
    }

    /**
     * @param  array<string, mixed>  $card
     * @param  array<string, mixed>  $row
     * @return array{
     *     id: int,
     *     title: string,
     *     subtitle: string,
     *     fields: list<array{label: string, value: string}>,
     *     badges: list<array{label: string, value: string, kind: string}>
     * }
     */
    private function cardPayload(string $model, array $card, array $row, Environment $env): array
    {
        $titleField = is_string($card['title'] ?? null) ? $card['title'] : 'name';
        $subtitleField = is_string($card['subtitle'] ?? null) ? $card['subtitle'] : null;

        return [
            'id' => (int) ($row['id'] ?? 0),
            'title' => $this->fieldText($model, ['name' => $titleField], $row[$titleField] ?? '', $env),
            'subtitle' => $subtitleField !== null
                ? $this->fieldText($model, ['name' => $subtitleField], $row[$subtitleField] ?? null, $env)
                : '',
            'fields' => $this->cardLines($model, $card['fields'] ?? [], $row, $env, false),
            'badges' => $this->cardLines($model, $card['badges'] ?? [], $row, $env, true),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $fields
     * @param  array<string, mixed>  $row
     * @return list<array{label: string, value: string, kind?: string}>
     */
    private function cardLines(
        string $model,
        array $fields,
        array $row,
        Environment $env,
        bool $badges,
    ): array {
        $lines = [];

        foreach ($fields as $fieldSpec) {
            if (! is_array($fieldSpec)) {
                continue;
            }

            $name = (string) ($fieldSpec['name'] ?? '');
            if ($name === '') {
                continue;
            }

            $lines[] = [
                'label' => $this->fieldLabel($model, $name, $env),
                'value' => $this->fieldText($model, $fieldSpec, $row[$name] ?? null, $env),
                'kind' => $badges && (($fieldSpec['widget'] ?? null) === 'toggle') ? 'toggle' : 'text',
            ];
        }

        return $lines;
    }

    /**
     * @param  array<string, mixed>  $fieldSpec
     */
    private function fieldText(string $model, array $fieldSpec, mixed $value, Environment $env): string
    {
        return $this->schemaBuilder->formatFieldValue($model, $fieldSpec, $value, $env);
    }

    private function fieldLabel(string $model, string $name, Environment $env): string
    {
        $field = $env->registry->modelClass($model)::fields()[$name] ?? null;

        if ($field === null) {
            return ucfirst(str_replace('_', ' ', $name));
        }

        $label = $field->displayLabel();

        return $label !== '' ? $label : ucfirst(str_replace('_', ' ', $name));
    }

    private function columnKey(mixed $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR);
    }

    private function columnSortValue(string $model, string $groupBy, mixed $value, Environment $env): string
    {
        return $this->schemaBuilder->formatGroupLabel($model, $groupBy, $value, $env);
    }

    /**
     * @return list<mixed>|list<list<mixed>>
     */
    private function groupDomain(string $groupBy, mixed $value, Environment $env, string $model): array
    {
        $field = $env->registry->modelClass($model)::fields()[$groupBy] ?? null;

        if ($field instanceof BooleanField) {
            return [[$groupBy, '=', (bool) $value]];
        }

        if ($field instanceof Many2oneField) {
            if ($value === false || $value === null || $value === 0) {
                return [[$groupBy, '=', false]];
            }

            return [[$groupBy, '=', (int) $value]];
        }

        return [[$groupBy, '=', $value]];
    }
}
