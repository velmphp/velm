<?php

declare(strict_types=1);

namespace Velm\Admin\Dashboard;

use Velm\Environment;
use Velm\Modules\Dashboard\DashboardWidgetSpec;

final class DashboardService
{
    public function __construct(
        private readonly DashboardCollector $collector = new DashboardCollector,
    ) {}

    /**
     * @param  list<string>  $roots
     * @return list<array<string, mixed>>
     */
    public function visibleWidgets(Environment $env, array $roots): array
    {
        $out = [];

        foreach ($this->collector->collect($roots) as $spec) {
            if (! $this->isVisible($env, $spec)) {
                continue;
            }

            $out[] = [
                ...$spec->toArray(),
                'data' => $this->resolveData($env, $spec),
            ];
        }

        return $out;
    }

    private function isVisible(Environment $env, DashboardWidgetSpec $spec): bool
    {
        if (! $env->registry->has($spec->model)) {
            return false;
        }

        return $env->hasAccess($spec->model, $spec->perm);
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveData(Environment $env, DashboardWidgetSpec $spec): array
    {
        if (! str_contains($spec->resolver, '::')) {
            throw new \RuntimeException("Dashboard resolver {$spec->resolver} must use Class::method syntax.");
        }

        [$class, $method] = explode('::', $spec->resolver, 2);

        if ($class === '' || $method === '' || ! class_exists($class) || ! method_exists($class, $method)) {
            throw new \RuntimeException("Dashboard resolver {$spec->resolver} is not callable.");
        }

        $data = $class::$method($env);

        return is_array($data) ? $data : [];
    }
}
