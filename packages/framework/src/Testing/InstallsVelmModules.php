<?php

declare(strict_types=1);

namespace Velm\Framework\Testing;

use Velm\Framework\VelmManager;
use Velm\Modules\ModuleRepository;

/**
 * Skips repeat migrate:fresh + Velm install on in-memory SQLite after the first test.
 *
 * Laravel restores the :memory: database from a post-migrate snapshot before each test,
 * but still calls migrateDatabases() when RefreshDatabaseState::$migrated is reset.
 */
trait InstallsVelmModules
{
    private static bool $velmTestingDatabaseReady = false;

    protected static function velmTestingDatabaseIsReady(): bool
    {
        return self::$velmTestingDatabaseReady;
    }

    protected function migrateDatabases(): void
    {
        if (self::$velmTestingDatabaseReady) {
            return;
        }

        $this->artisan('migrate:fresh', $this->migrateFreshUsing());
        $this->bootstrapVelmForTests();

        self::$velmTestingDatabaseReady = true;
    }

    protected function bootstrapVelmForTests(): void
    {
        if (! $this->shouldInstallVelmModules()) {
            return;
        }

        $manager = $this->app->make(VelmManager::class);
        $manager->installBootstrap($this->velmBootstrapModules());

        foreach ($this->velmApplicationModules() as $module) {
            $manager->install($module);
        }
    }

    protected function shouldInstallVelmModules(): bool
    {
        $repository = $this->app->make(ModuleRepository::class);

        foreach ([...$this->velmBootstrapModules(), ...$this->velmApplicationModules()] as $module) {
            if (! $repository->isInstalled($module)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    protected function velmBootstrapModules(): array
    {
        /** @var list<string> $modules */
        $modules = $this->app['config']->get('velm.bootstrap_modules', ['base']);

        return $modules;
    }

    /**
     * @return list<string>
     */
    protected function velmApplicationModules(): array
    {
        return ['partners'];
    }
}
