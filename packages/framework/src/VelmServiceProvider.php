<?php

declare(strict_types=1);

namespace Velm\Framework;

use Illuminate\Support\ServiceProvider;
use Velm\Environment;
use Velm\Filament\FilamentServiceProvider;
use Velm\Framework\VelmManager;
use Velm\Modules\ModulesServiceProvider;
use Velm\Views\ViewsServiceProvider;
use Velm\Web\WebServiceProvider;

final class VelmServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(ModulesServiceProvider::class);
        $this->app->register(ViewsServiceProvider::class);
        $this->app->register(WebServiceProvider::class);
        $this->app->register(FilamentServiceProvider::class);
        $this->mergeConfigFrom(__DIR__.'/../config/velm.php', 'velm');

        $this->app->singleton(VelmManager::class);
        $this->app->alias(VelmManager::class, 'velm');
        $this->app->singleton(
            Environment::class,
            static fn ($app): Environment => $app->make(VelmManager::class)->environment(),
        );
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/velm.php' => config_path('velm.php'),
            ], 'velm-config');

            $this->commands([
                Console\MigrateCommand::class,
                Console\CronRunCommand::class,
                Console\ModuleInstallCommand::class,
                Console\ModuleSyncCommand::class,
                Console\ModuleListCommand::class,
            ]);
        }
    }
}
