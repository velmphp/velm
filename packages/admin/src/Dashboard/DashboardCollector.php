<?php

declare(strict_types=1);

namespace Velm\Admin\Dashboard;

use Velm\Modules\Dashboard\DashboardData;
use Velm\Modules\Dashboard\DashboardWidgetSpec;
use Velm\Modules\ModuleDiscovery;
use Velm\Modules\ModuleRepository;

final class DashboardCollector
{
    public function __construct(
        private readonly ModuleDiscovery $discovery = new ModuleDiscovery,
        private readonly ModuleRepository $repository = new ModuleRepository,
    ) {}

    /**
     * @param  list<string>  $roots
     * @return list<DashboardWidgetSpec>
     */
    public function collect(array $roots): array
    {
        $specs = $this->discovery->discover($roots);
        $widgets = [];

        foreach ($this->repository->installedNames() as $moduleName) {
            $spec = $specs[$moduleName] ?? null;

            if ($spec === null) {
                continue;
            }

            $file = $spec->path.DIRECTORY_SEPARATOR.'dashboard.php';

            if (! is_file($file)) {
                continue;
            }

            $data = require $file;

            if (! $data instanceof DashboardData) {
                throw new \RuntimeException("{$file} must return a DashboardData instance.");
            }

            foreach ($data->widgets() as $widget) {
                $widgets[] = $widget;
            }
        }

        usort(
            $widgets,
            static fn (DashboardWidgetSpec $a, DashboardWidgetSpec $b): int => $a->sequence <=> $b->sequence
                ?: strcmp($a->module, $b->module)
                ?: strcmp($a->id, $b->id),
        );

        return $widgets;
    }
}
