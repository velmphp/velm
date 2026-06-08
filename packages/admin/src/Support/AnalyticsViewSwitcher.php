<?php

declare(strict_types=1);

namespace Velm\Admin\Support;

use Velm\Environment;

final class AnalyticsViewSwitcher
{
    /**
     * @return list<array{type: string, label: string, url: string, active: bool}>
     */
    public function items(Environment $env, string $module, string $currentView, string $model): array
    {
        if ($model === '' || ! $env->registry->has('ir.ui.view')) {
            return [];
        }

        $labels = [
            'list' => 'List',
            'kanban' => 'Kanban',
            'graph' => 'Graph',
            'pivot' => 'Pivot',
        ];
        $order = array_keys($labels);
        $views = $env->model('ir.ui.view')->search([
            ['module', '=', $module],
            ['model', '=', $model],
        ])->read();

        $items = [];

        foreach ($views as $view) {
            $type = (string) ($view['view_type'] ?? '');

            if (! isset($labels[$type])) {
                continue;
            }

            $name = (string) ($view['name'] ?? '');

            if ($name === '') {
                continue;
            }

            $items[] = [
                'type' => $type,
                'label' => $labels[$type],
                'url' => ArchPageMap::pageUrlForView($module, $name)
                    ?? StoredViewRoutes::viewPageUrl($module, $name),
                'active' => $name === $currentView,
                'order' => array_search($type, $order, true),
            ];
        }

        usort(
            $items,
            static fn (array $left, array $right): int => (int) $left['order'] <=> (int) $right['order'],
        );

        return array_map(
            static fn (array $item): array => [
                'type' => $item['type'],
                'label' => $item['label'],
                'url' => $item['url'],
                'active' => $item['active'],
            ],
            $items,
        );
    }
}
