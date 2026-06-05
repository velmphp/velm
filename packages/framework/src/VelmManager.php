<?php

declare(strict_types=1);

namespace Velm\Framework;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\DB;
use Velm\Environment;
use Velm\Modules\ModuleInstaller;
use Velm\Modules\Database\LaravelConnection;
use Velm\Modules\Schema\VelmSchemaReset;

final class VelmManager
{
    public function __construct(
        private readonly Application $app,
        private readonly ModuleInstaller $installer = new ModuleInstaller,
    ) {}

    /**
     * @return list<string>
     */
    public function addonPaths(): array
    {
        /** @var list<string> $paths */
        $paths = $this->app['config']->get('velm.addon_paths', []);

        return $paths;
    }

    /**
     * @return list<string>
     */
    public function bootstrapModules(): array
    {
        /** @var list<string> $modules */
        $modules = $this->app['config']->get('velm.bootstrap_modules', ['base', 'admin']);

        return $modules;
    }

    public function environment(): Environment
    {
        return $this->installer->environment($this->addonPaths());
    }

    /**
     * @param  list<string>  $modules
     */
    public function installBootstrap(array $modules = []): void
    {
        $modules = $modules === [] ? $this->bootstrapModules() : $modules;

        $this->installer->installBootstrap($this->addonPaths(), $modules);
    }

    public function install(string $moduleName): void
    {
        $this->installer->install($moduleName, $this->addonPaths());
    }

    public function upgrade(string $moduleName): void
    {
        $this->installer->reconcile($moduleName, $this->addonPaths());
    }

    public function sync(string $moduleName): void
    {
        $this->installer->sync($moduleName, $this->addonPaths());
    }

    public function uninstall(string $moduleName): void
    {
        $this->installer->uninstall($moduleName, $this->addonPaths(), $this->bootstrapModules());
    }

    public function uninstallPreview(string $moduleName): \Velm\Modules\ModuleUninstallPreview
    {
        return $this->installer->uninstallPreview($moduleName, $this->addonPaths(), $this->bootstrapModules());
    }

    public function seed(?string $module = null): void
    {
        $this->installer->seed($this->addonPaths(), $module);
    }

    /**
     * Drop all Velm-owned tables, then reinstall bootstrap modules and optionally
     * migrate additional modules.
     *
     * @param  list<string>  $bootstrapModules
     * @param  list<string>  $modules
     */
    public function migrateFresh(array $bootstrapModules = [], array $modules = []): void
    {
        $roots = $this->addonPaths();
        $bootstrapModules = $bootstrapModules === [] ? $this->bootstrapModules() : $bootstrapModules;

        VelmSchemaReset::make(new LaravelConnection(DB::connection()), $roots)->reset();

        $this->installer->installBootstrap($roots, $bootstrapModules);

        foreach (array_values(array_unique($modules)) as $module) {
            $this->installer->migrate($module, $roots);
        }
    }
}
