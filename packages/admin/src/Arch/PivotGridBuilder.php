<?php

declare(strict_types=1);

namespace Velm\Admin\Arch;

use Velm\Environment;
use Velm\Views\Arch\ArchNormalizer;

final class PivotGridBuilder
{
    public function __construct(
        private readonly AnalyticsDomainBuilder $domainBuilder = new AnalyticsDomainBuilder,
        private readonly AnalyticsMeasures $measures = new AnalyticsMeasures,
        private readonly ArchSchemaBuilder $schemaBuilder = new ArchSchemaBuilder,
    ) {}

    /**
     * @param  array<string, mixed>  $arch
     * @return array{
     *     rows: list<string>,
     *     cols: list<string>,
     *     measures: list<string>,
     *     measure_labels: array<string, string>,
     *     row_headers: list<array{key: string, label: string}>,
     *     col_headers: list<array{key: string, label: string}>,
     *     matrix: array<string, array<string, array<string, int|float|null>>>,
     *     has_cols: bool
     * }
     */
    public function build(array $arch, Environment $env): array
    {
        $arch = ArchNormalizer::normalize($arch, 'pivot');
        $model = (string) ($arch['model'] ?? '');
        $rows = $arch['rows'] ?? [];
        $cols = $arch['cols'] ?? [];
        $measureSpecs = $arch['measures'] ?? ['__count'];
        $measures = is_array($measureSpecs) ? array_values(array_map(strval(...), $measureSpecs)) : ['__count'];
        $domain = $this->domainBuilder->build($arch);
        $groupby = array_merge($rows, $cols);
        $groups = $env->model($model)->readGroup(
            $domain,
            $this->measures->aggregateFields($measures),
            $groupby,
        );

        $rowHeaders = [];
        $colHeaders = [];
        $matrix = [];

        foreach ($groups as $group) {
            $rowKey = $this->dimensionKey($rows, $group);
            $colKey = $cols === [] ? '__total__' : $this->dimensionKey($cols, $group);
            $rowHeaders[$rowKey] = [
                'key' => $rowKey,
                'label' => $this->dimensionLabel($rows, $group, $model, $env),
            ];

            if ($cols !== []) {
                $colHeaders[$colKey] = [
                    'key' => $colKey,
                    'label' => $this->dimensionLabel($cols, $group, $model, $env),
                ];
            }

            $matrix[$rowKey][$colKey] = $this->measures->values($measures, $group);
        }

        uasort($rowHeaders, static fn (array $left, array $right): int => $left['label'] <=> $right['label']);
        uasort($colHeaders, static fn (array $left, array $right): int => $left['label'] <=> $right['label']);

        if ($cols === []) {
            $colHeaders = [
                '__total__' => [
                    'key' => '__total__',
                    'label' => 'Total',
                ],
            ];
        }

        return [
            'rows' => $rows,
            'cols' => $cols,
            'measures' => $measures,
            'measure_labels' => $this->measures->labels($measures),
            'row_headers' => array_values($rowHeaders),
            'col_headers' => array_values($colHeaders),
            'matrix' => $matrix,
            'has_cols' => $cols !== [],
        ];
    }

    /**
     * @param  list<string>  $dimensions
     * @param  array<string, mixed>  $group
     */
    private function dimensionKey(array $dimensions, array $group): string
    {
        $parts = [];

        foreach ($dimensions as $dimension) {
            $parts[] = $group[$dimension] ?? null;
        }

        return json_encode($parts, JSON_THROW_ON_ERROR);
    }

    /**
     * @param  list<string>  $dimensions
     * @param  array<string, mixed>  $group
     */
    private function dimensionLabel(array $dimensions, array $group, string $model, Environment $env): string
    {
        $labels = [];

        foreach ($dimensions as $dimension) {
            $labels[] = $this->schemaBuilder->formatGroupLabel(
                $model,
                $dimension,
                $group[$dimension] ?? null,
                $env,
            );
        }

        return implode(' / ', $labels);
    }
}
