<?php

declare(strict_types=1);

namespace Velm\Modules;

use Velm\Views\Contracts\SyncsModuleViews;

final class ModuleViewSync implements SyncsModuleViews
{
    public function __construct(
        private readonly ModuleInstaller $installer = new ModuleInstaller,
        private readonly ModuleRepository $repository = new ModuleRepository,
    ) {}

    public function isInstalled(string $module): bool
    {
        return $this->repository->isInstalled($module);
    }

    public function sync(string $module): void
    {
        /** @var list<string> $roots */
        $roots = config('velm.addon_paths', []);

        $this->installer->sync($module, $roots);
    }
}
