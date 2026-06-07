<?php

declare(strict_types=1);

namespace Velm\Admin\Arch;

use Velm\Environment;
use Velm\Views\Arch\ArchNormalizer;

/**
 * Builds PyVelm-shaped pivot JSON for interactive toolbars and APIs.
 */
final class PivotDataBuilder
{
    public function __construct(
        private readonly AnalyticsDomainBuilder $domainBuilder = new AnalyticsDomainBuilder,
        private readonly AnalyticsSearchDomain $searchDomain = new AnalyticsSearchDomain,
        private readonly AnalyticsMeasures $measuresHelper = new AnalyticsMeasures,
        private readonly ArchSchemaBuilder $schemaBuilder = new ArchSchemaBuilder,
    ) {}

    /**
     * @param  array<string, mixed>  $arch
     * @param  list<string>  $rowSpecs
     * @param  list<string>  $colSpecs
     * @param  list<string>  $measureSpecs
     * @return array<string, mixed>
     */
    public function build(
        array $arch,
        Environment $env,
        array $rowSpecs = [],
        array $colSpecs = [],
        array $measureSpecs = [],
        string $search = '',
    ): array {
        $arch = ArchNormalizer::normalize($arch, 'pivot');
        $model = (string) ($arch['model'] ?? '');
        $rows = $rowSpecs !== [] ? $rowSpecs : ($arch['rows'] ?? []);
        $cols = $colSpecs !== [] ? $colSpecs : ($arch['cols'] ?? []);
        $measures = $measureSpecs !== [] ? $measureSpecs : ($arch['measures'] ?? ['__count']);
        $domain = $this->searchDomain->build(
            $model,
            $env,
            $search,
            $this->domainBuilder->build($arch),
        );
        $groupby = array_merge($rows, $cols);
        $groups = $groupby === []
            ? []
            : $env->model($model)->readGroup(
                $domain,
                $this->measuresHelper->aggregateFields($measures),
                $groupby,
            );

        $rowAxes = $this->axisLabels($groups, $rows, $model, $env);
        $colAxes = $this->axisLabels($groups, $cols, $model, $env);
        $cellIndex = [];

        foreach ($groups as $group) {
            $rowKey = $this->comboKey($rows, $group);
            $colKey = $this->comboKey($cols, $group);
            $values = [];

            foreach ($measures as $measure) {
                $values[$measure] = $this->measuresHelper->value($measure, $group);
            }

            $cellIndex[$rowKey][$colKey] = $values;
        }

        $rowCombos = $this->combos($rowAxes);
        $colCombos = $this->combos($colAxes);

        if ($colCombos === []) {
            $colCombos = [[]];
        }

        $headerLevels = [];
        foreach ($colAxes as $index => $level) {
            $spanPer = max(1, count($level));

            for ($below = $index + 1, $belowCount = count($colAxes); $below < $belowCount; $below++) {
                $spanPer *= max(1, count($colAxes[$below]));
            }

            $headerLevels[] = array_map(
                static fn (array $entry): array => [
                    'label' => $entry['label'],
                    'colspan' => $spanPer * count($measures),
                ],
                $level,
            );
        }

        $measureLabelRow = [];
        foreach ($colCombos as $_col) {
            foreach ($measures as $measure) {
                $measureLabelRow[] = [
                    'label' => $this->measuresHelper->label($measure),
                    'colspan' => 1,
                ];
            }
        }

        $bodyRows = [];
        foreach ($rowCombos as $rowCombo) {
            $rowLabels = $this->comboLabels($rowCombo, $rowAxes);
            $cells = [];
            $rowTotals = array_fill_keys($measures, 0.0);

            foreach ($colCombos as $colCombo) {
                $values = $cellIndex[$rowCombo][$colCombo] ?? null;

                foreach ($measures as $measure) {
                    $raw = $values[$measure] ?? null;
                    $cells[] = [
                        'display' => $this->formatCell($raw, $measure),
                        'is_total' => false,
                    ];

                    if (is_numeric($raw)) {
                        $rowTotals[$measure] += (float) $raw;
                    }
                }
            }

            foreach ($measures as $measure) {
                $cells[] = [
                    'display' => $this->formatCell($rowTotals[$measure], $measure),
                    'is_total' => true,
                ];
            }

            $bodyRows[] = [
                'labels' => $rowLabels,
                'cells' => $cells,
            ];
        }

        $colTotals = [];
        $grandTotals = array_fill_keys($measures, 0.0);

        foreach ($colCombos as $colCombo) {
            foreach ($measures as $measure) {
                $run = 0.0;

                foreach ($rowCombos as $rowCombo) {
                    $values = $cellIndex[$rowCombo][$colCombo] ?? null;
                    $raw = $values[$measure] ?? null;

                    if (is_numeric($raw)) {
                        $run += (float) $raw;
                    }
                }

                $colTotals[] = [
                    'display' => $this->formatCell($run, $measure),
                    'is_total' => true,
                ];
                $grandTotals[$measure] += $run;
            }
        }

        foreach ($measures as $measure) {
            $colTotals[] = [
                'display' => $this->formatCell($grandTotals[$measure], $measure),
                'is_total' => true,
            ];
        }

        return [
            'header_levels' => $headerLevels,
            'measure_label_row' => $measureLabelRow,
            'grand_header' => ['label' => 'Total', 'colspan' => count($measures)],
            'body_rows' => $bodyRows,
            'col_totals' => $colTotals,
            'row_axis_titles' => $this->axisTitles($rows, $model, $env),
            'measure_count' => count($measures),
            'col_combos_count' => count($colCombos),
            'row_specs' => $rows,
            'col_specs' => $cols,
            'measures' => $measures,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $groups
     * @param  list<string>  $specs
     * @return list<list<array{value: mixed, label: string}>>
     */
    private function axisLabels(array $groups, array $specs, string $model, Environment $env): array
    {
        if ($specs === []) {
            return [];
        }

        $axes = array_fill(0, count($specs), []);

        foreach ($groups as $group) {
            foreach ($specs as $index => $spec) {
                $field = explode(':', $spec, 2)[0];
                $value = $group[$field] ?? null;
                $key = json_encode($value, JSON_THROW_ON_ERROR);

                if (! isset($axes[$index][$key])) {
                    $axes[$index][$key] = [
                        'value' => $value,
                        'label' => $this->schemaBuilder->formatGroupLabel($model, $field, $value, $env),
                    ];
                }
            }
        }

        return array_map(
            static fn (array $level): array => array_values($level),
            $axes,
        );
    }

    /**
     * @param  list<list<array{value: mixed, label: string}>>  $axes
     * @return list<list<mixed>>
     */
    private function combos(array $axes): array
    {
        if ($axes === []) {
            return [[]];
        }

        $result = [[]];

        foreach ($axes as $level) {
            $next = [];

            foreach ($result as $prefix) {
                foreach ($level as $entry) {
                    $next[] = [...$prefix, $entry['value']];
                }
            }

            $result = $next;
        }

        return array_map(
            static fn (array $combo): string => json_encode($combo, JSON_THROW_ON_ERROR),
            $result,
        );
    }

    /**
     * @param  list<string>  $specs
     * @param  array<string, mixed>  $group
     */
    private function comboKey(array $specs, array $group): string
    {
        $values = [];

        foreach ($specs as $spec) {
            $field = explode(':', $spec, 2)[0];
            $values[] = $group[$field] ?? null;
        }

        return json_encode($values, JSON_THROW_ON_ERROR);
    }

    /**
     * @param  list<list<array{value: mixed, label: string}>>  $axes
     * @return list<string>
     */
    private function comboLabels(string $comboKey, array $axes): array
    {
        $values = json_decode($comboKey, true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($values)) {
            return [];
        }

        $labels = [];

        foreach ($values as $index => $value) {
            $level = $axes[$index] ?? [];
            $label = null;

            foreach ($level as $entry) {
                if ($entry['value'] == $value) {
                    $label = $entry['label'];
                    break;
                }
            }

            $labels[] = $label ?? (string) $value;
        }

        return $labels;
    }

    /**
     * @param  list<string>  $specs
     * @return list<string>
     */
    private function axisTitles(array $specs, string $model, Environment $env): array
    {
        $titles = [];

        foreach ($specs as $spec) {
            [$field, $granularity] = array_pad(explode(':', $spec, 2), 2, null);
            $modelClass = $env->registry->modelClass($model);
            $fieldObj = $modelClass::fields()[$field] ?? null;
            $title = $fieldObj?->displayLabel() ?? ucfirst(str_replace('_', ' ', $field));

            if ($granularity !== null && $granularity !== '') {
                $title .= ' ('.$granularity.')';
            }

            $titles[] = $title;
        }

        return $titles;
    }

    private function formatCell(mixed $value, string $measure): string
    {
        if ($value === null) {
            return '—';
        }

        if ($measure === '__count') {
            return (string) (int) $value;
        }

        if (str_contains($measure, ':avg')) {
            return number_format((float) $value, 2, '.', '');
        }

        if (is_numeric($value)) {
            return (string) (int) round((float) $value);
        }

        return (string) $value;
    }
}
