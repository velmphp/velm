<?php

declare(strict_types=1);

namespace Velm\Admin\Concerns;

use Velm\Modules\ModuleInstaller;

trait ReconcilesVelmModuleUi
{
    protected function reconcileVelmModuleUi(?string $module = null): void
    {
        $module ??= method_exists($this, 'velmViewModule')
            ? $this->velmViewModule()
            : null;

        if (! is_string($module) || $module === '') {
            return;
        }

        $roots = config('velm.addon_paths', []);

        if (! is_array($roots)) {
            return;
        }

        $paths = array_values(array_filter(
            $roots,
            static fn (mixed $path): bool => is_string($path) && $path !== '',
        ));

        if ($paths === []) {
            return;
        }

        $installer = app(ModuleInstaller::class);

        if (! $installer->hasPendingUiSync($module, $paths)) {
            return;
        }

        $installer->sync($module, $paths);
    }
}
