<?php

declare(strict_types=1);

namespace Velm\Modules;

use Illuminate\Support\ServiceProvider;
use Velm\Views\Contracts\SyncsModuleViews;

final class ModulesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ModuleInstaller::class);
        $this->app->singleton(SyncsModuleViews::class, ModuleViewSync::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
