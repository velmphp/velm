<?php

declare(strict_types=1);

namespace Velm\Framework;

use Illuminate\Contracts\Foundation\Application;
use Velm\Environment;
use Velm\Modules\ModuleInstaller;

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
}
