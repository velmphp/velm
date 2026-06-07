<?php

declare(strict_types=1);

namespace Velm\Admin\Arch;

use Velm\Environment;
use Velm\Views\Arch\ArchNormalizer;

final class GraphDataBuilder
{
    public function __construct(
        private readonly AnalyticsDomainBuilder $domainBuilder = new AnalyticsDomainBuilder,
        private readonly AnalyticsSearchDomain $searchDomain = new AnalyticsSearchDomain,
        private readonly AnalyticsMeasures $measures = new AnalyticsMeasures,
        private readonly ArchSchemaBuilder $schemaBuilder = new ArchSchemaBuilder,
    ) {}

    /**
     * @param  array<string, mixed>  $arch
     * @return array{
     *     chart: string,
     *     group_by: string,
     *     measure: string,
     *     measures: list<string>,
     *     measure_labels: array<string, string>,
     *     measure_label: string,
     *     labels: list<string>,
     *     values: list<float>,
     *     points: list<array{
     *         label: string,
     *         value: int|float,
     *         count: int,
     *         measures: array<string, int|float|null>,
     *         domain: list<mixed>|list<list<mixed>>
     *     }>,
     *     max_value: int|float
     * }
     */
    public function build(
        array $arch,
        Environment $env,
        ?string $groupBy = null,
        ?string $measure = null,
        string $search = '',
    ): array {
        $arch = ArchNormalizer::normalize($arch, 'graph');
        $model = (string) ($arch['model'] ?? '');
        $resolvedGroupBy = $groupBy !== null && $groupBy !== ''
            ? $groupBy
            : (string) ($arch['group_by'] ?? '');
        $measureSpecs = $arch['measures'] ?? ['__count'];
        $archMeasures = is_array($measureSpecs) ? array_values(array_map(strval(...), $measureSpecs)) : ['__count'];
        $resolvedMeasure = $measure !== null && $measure !== ''
            ? $measure
            : ($archMeasures[0] ?? '__count');
        $measures = [$resolvedMeasure];
        $domain = $this->searchDomain->build(
            $model,
            $env,
            $search,
            $this->domainBuilder->build($arch),
        );
        $groups = $env->model($model)->readGroup(
            $domain,
            $this->measures->aggregateFields($measures),
            [$resolvedGroupBy],
        );

        $points = [];
        $maxValue = 0.0;
        $groupField = explode(':', $resolvedGroupBy, 2)[0];

        foreach ($groups as $group) {
            $value = (float) ($this->measures->primaryValue($measures, $group) ?? 0);
            $maxValue = max($maxValue, $value);
            $raw = $group[$groupField] ?? null;

            $points[] = [
                'label' => $this->schemaBuilder->formatGroupLabel($model, $groupField, $raw, $env),
                'value' => $value,
                'count' => (int) ($group['__count'] ?? 0),
                'measures' => $this->measures->values($measures, $group),
                'domain' => $group['__domain'] ?? [],
            ];
        }

        usort(
            $points,
            static fn (array $left, array $right): int => $right['value'] <=> $left['value'],
        );

        $measureLabels = $this->measures->labels($measures);

        return [
            'chart' => (string) ($arch['chart'] ?? 'bar'),
            'group_by' => $resolvedGroupBy,
            'measure' => $resolvedMeasure,
            'measures' => $measures,
            'measure_labels' => $measureLabels,
            'measure_label' => $measureLabels[$resolvedMeasure] ?? 'Count',
            'labels' => array_column($points, 'label'),
            'values' => array_map(static fn (array $point): float => (float) $point['value'], $points),
            'points' => $points,
            'max_value' => $maxValue > 0 ? $maxValue : 1,
        ];
    }
}
