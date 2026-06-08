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
        $boardModel = (string) ($arch['model'] ?? '');
        $boardDomain = $this->domainBuilder->build($arch);
        $listView = is_string($arch['list_view'] ?? null) ? $arch['list_view'] : null;
        $widgets = [];

        foreach ($arch['widgets'] as $spec) {
            $built = match ($spec['type']) {
                'stat' => $this->buildStatWidget($spec, $boardModel, $boardDomain, $env, $module, $listView),
                'table' => $this->buildTableWidget($spec, $env, $module),
                'chart' => $this->buildChartWidget($spec, $env, $module),
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

        return [
            'id' => (string) $spec['id'],
            'title' => (string) ($spec['title'] ?? $spec['id']),
            'size' => (string) ($spec['size'] ?? 'half'),
            'span_class' => $this->spanClass((string) ($spec['size'] ?? 'half')),
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
    private function buildTableWidget(array $spec, Environment $env, string $module): ?array
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

        return [
            'id' => (string) $spec['id'],
            'title' => (string) ($spec['title'] ?? $spec['id']),
            'size' => (string) ($spec['size'] ?? 'half'),
            'span_class' => $this->spanClass((string) ($spec['size'] ?? 'half')),
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
    private function buildChartWidget(array $spec, Environment $env, string $module): ?array
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
        $points = [];

        foreach (array_slice($graph['points'], 0, 5) as $point) {
            if (! is_array($point)) {
                continue;
            }

            $points[] = [
                'label' => (string) ($point['label'] ?? ''),
                'value' => (float) ($point['value'] ?? 0),
            ];
        }

        return [
            'id' => (string) $spec['id'],
            'title' => (string) ($spec['title'] ?? $spec['id']),
            'size' => (string) ($spec['size'] ?? 'half'),
            'span_class' => $this->spanClass((string) ($spec['size'] ?? 'half')),
            'icon' => (string) ($spec['icon'] ?? 'heroicon-o-chart-bar'),
            'view' => 'velm-ui::dashboard.chart-card',
            'data' => [
                'points' => $points,
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

    private function spanClass(string $size): string
    {
        return match ($size) {
            'full' => 'md:col-span-2 xl:col-span-3',
            'third' => 'xl:col-span-1',
            default => 'md:col-span-1',
        };
    }
}
