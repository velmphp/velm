<?php

declare(strict_types=1);

namespace Velm\Admin\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Velm\Environment;
use Velm\Admin\AdminServiceProvider as VelmAdminServiceProvider;
use Velm\Framework\VelmManager;
use Velm\Framework\VelmServiceProvider;
use Velm\Modules\ModulesServiceProvider;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [
            ModulesServiceProvider::class,
            VelmServiceProvider::class,
            LivewireServiceProvider::class,
            VelmAdminServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        $app['config']->set('velm.addon_paths', [
            dirname(__DIR__, 2).'/modules/modules',
        ]);

        $app['config']->set('velm.bootstrap_modules', ['base']);

        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(dirname(__DIR__, 2).'/modules/database/migrations');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $manager = $this->app->make(VelmManager::class);
        $manager->installBootstrap(['base']);
        $manager->install('partners');
    }
}
