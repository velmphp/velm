<?php

declare(strict_types=1);

namespace Velm\Admin\Arch;

use Velm\Admin\Pages\StoredViewRecordPage;
use Velm\Admin\Support\StoredViewRoutes;
use Velm\Environment;
use Velm\Views\Arch\ArchNormalizer;
use Velm\Views\ViewRegistry;

final class DashboardBoardBuilder
{
    public function __construct(
        private readonly AnalyticsDomainBuilder $domainBuilder = new AnalyticsDomainBuilder,
        private readonly ArchSchemaBuilder $schemaBuilder = new ArchSchemaBuilder,
        private readonly GraphDataBuilder $graphDataBuilder = new GraphDataBuilder,
        private readonly ViewRegistry $viewRegistry = new ViewRegistry,
    ) {}

    /**
     * @param  array<string, mixed>  $arch
     * @return array{columns: int, widgets: list<array<string, mixed>>}
     */
    public function build(array $arch, Environment $env, string $module): array
    {
        $arch = ArchNormalizer::normalize($arch, 'dashboard');
        $boardColumns = (int) $arch['columns'];
        $boardModel = (string) ($arch['model'] ?? '');
        $boardDomain = $this->domainBuilder->build($arch);
        $listView = is_string($arch['list_view'] ?? null) ? $arch['list_view'] : null;
        $widgets = [];

        foreach ($arch['widgets'] as $spec) {
            $built = match ($spec['type']) {
                'stat' => $this->buildStatWidget($spec, $boardModel, $boardDomain, $env, $module, $listView, $boardColumns),
                'table' => $this->buildTableWidget($spec, $env, $module, $boardColumns),
                'chart' => $this->buildChartWidget($spec, $env, $module, $boardColumns),
                default => null,
            };

            if ($built !== null) {
                $widgets[] = $built;
            }
        }

        return [
            'columns' => (int) $arch['columns'],
            'widgets' => $widgets,
        ];
    }

    /**
     * @param  array<string, mixed>  $spec
     * @param  list<mixed>|list<list<mixed>>  $boardDomain
     * @return array<string, mixed>|null
     */
    private function buildStatWidget(
        array $spec,
        string $boardModel,
        array $boardDomain,
        Environment $env,
        string $module,
        ?string $listView,
        int $boardColumns,
    ): ?array {
        $model = (string) ($spec['model'] ?? $boardModel);

        if ($model === '' || ! $env->registry->has($model) || ! $env->hasAccess($model, 'read')) {
            return null;
        }

        $domain = array_merge(
            $boardDomain,
            is_array($spec['domain'] ?? null) ? $spec['domain'] : [],
        );
        $value = $env->model($model)->search($domain)->count();
        $href = $listView !== null && $listView !== ''
            ? StoredViewRoutes::viewPageUrl($module, $listView)
            : null;

        $colspan = $this->resolveColspan($spec);

        return [
            'id' => (string) $spec['id'],
            'title' => (string) ($spec['title'] ?? $spec['id']),
            'colspan' => $colspan,
            'span_class' => $this->spanClass($colspan, $boardColumns),
            'icon' => (string) ($spec['icon'] ?? 'heroicon-o-chart-bar'),
            'view' => 'velm-ui::dashboard.stat-card',
            'data' => [
                'value' => $value,
                'href' => $href,
                'action_label' => __('View all'),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $spec
     * @return array<string, mixed>|null
     */
    private function buildTableWidget(array $spec, Environment $env, string $module, int $boardColumns): ?array
    {
        $viewName = (string) ($spec['view'] ?? '');

        if ($viewName === '') {
            return null;
        }

        try {
            $listArch = $this->viewRegistry->arch($env, $module, $viewName);
        } catch (\Throwable) {
            return null;
        }

        $model = (string) ($listArch['model'] ?? '');

        if ($model === '' || ! $env->hasAccess($model, 'read')) {
            return null;
        }

        $listArch = ArchNormalizer::normalizeList($listArch);
        $fields = is_array($listArch['fields'] ?? null) ? $listArch['fields'] : [];
        $labelField = $this->resolveLabelField($fields);
        $limit = max(1, (int) ($spec['limit'] ?? 5));
        $rows = $env->model($model)->search([], order: 'id desc', limit: $limit)->read();
        $detailView = is_string($listArch['detail_view'] ?? null) ? $listArch['detail_view'] : null;
        $items = [];

        foreach ($rows as $row) {
            $recordId = (int) ($row['id'] ?? 0);
            $label = $labelField !== null
                ? $this->schemaBuilder->formatFieldValue($model, ['name' => $labelField], $row[$labelField] ?? null, $env)
                : '#'.$recordId;

            $item = [
                'label' => $label !== '' ? $label : '#'.$recordId,
            ];

            if ($detailView !== null && $recordId > 0) {
                $item['href'] = StoredViewRecordPage::getUrl([
                    'module' => $module,
                    'viewName' => $detailView,
                    'record' => $recordId,
                ], panel: 'velm');
            }

            $items[] = $item;
        }

        $colspan = $this->resolveColspan($spec);

        return [
            'id' => (string) $spec['id'],
            'title' => (string) ($spec['title'] ?? $spec['id']),
            'colspan' => $colspan,
            'span_class' => $this->spanClass($colspan, $boardColumns),
            'icon' => (string) ($spec['icon'] ?? 'heroicon-o-queue-list'),
            'view' => 'velm-ui::dashboard.list-card',
            'data' => [
                'items' => $items,
                'empty_label' => __('No records yet'),
                'href' => StoredViewRoutes::viewPageUrl($module, $viewName),
                'action_label' => __('View all'),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $spec
     * @return array<string, mixed>|null
     */
    private function buildChartWidget(array $spec, Environment $env, string $module, int $boardColumns): ?array
    {
        $viewName = (string) ($spec['view'] ?? '');

        if ($viewName === '') {
            return null;
        }

        try {
            $graphArch = $this->viewRegistry->arch($env, $module, $viewName);
        } catch (\Throwable) {
            return null;
        }

        $model = (string) ($graphArch['model'] ?? '');

        if ($model === '' || ! $env->hasAccess($model, 'read')) {
            return null;
        }

        $graph = $this->graphDataBuilder->build($graphArch, $env);
        $limit = 10;
        $labels = array_slice($graph['labels'], 0, $limit);
        $values = array_slice($graph['values'], 0, $limit);

        $colspan = $this->resolveColspan($spec);

        return [
            'id' => (string) $spec['id'],
            'title' => (string) ($spec['title'] ?? $spec['id']),
            'colspan' => $colspan,
            'span_class' => $this->spanClass($colspan, $boardColumns),
            'icon' => (string) ($spec['icon'] ?? 'heroicon-o-chart-bar'),
            'view' => 'velm-ui::dashboard.chart-card',
            'data' => [
                'labels' => $labels,
                'values' => $values,
                'chart_type' => (string) ($graph['chart'] ?? 'bar'),
                'measure_label' => (string) ($graph['measure_label'] ?? ''),
                'href' => StoredViewRoutes::viewPageUrl($module, $viewName),
                'action_label' => __('Open chart'),
            ],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $fields
     */
    private function resolveLabelField(array $fields): ?string
    {
        foreach ($fields as $field) {
            if (($field['name'] ?? null) === 'name') {
                return 'name';
            }
        }

        $first = $fields[0]['name'] ?? null;

        return is_string($first) && $first !== '' ? $first : null;
    }

    /**
     * @param  array<string, mixed>  $spec
     */
    private function resolveColspan(array $spec): int|string
    {
        if (($spec['colspan'] ?? null) === 'full') {
            return 'full';
        }

        return max(1, (int) ($spec['colspan'] ?? 1));
    }

    private function spanClass(int|string $colspan, int $boardColumns): string
    {
        $span = $colspan === 'full' ? $boardColumns : (int) $colspan;
        $span = max(1, min($span, max(1, $boardColumns)));

        if ($boardColumns >= 3) {
            if ($span >= $boardColumns) {
                return 'md:col-span-2 xl:col-span-3';
            }

            return $span >= 2
                ? 'md:col-span-2 xl:col-span-2'
                : 'md:col-span-1 xl:col-span-1';
        }

        if ($boardColumns >= 2) {
            return $span >= 2 ? 'md:col-span-2' : 'md:col-span-1';
        }

        return 'col-span-1';
    }
}
