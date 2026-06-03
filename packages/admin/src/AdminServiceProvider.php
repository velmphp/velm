<?php

declare(strict_types=1);

namespace Velm\Admin;

use Illuminate\Support\ServiceProvider;
use Velm\Admin\Arch\ArchSchemaBuilder;
use Velm\Admin\Arch\ArchTableConfigurator;
use Velm\Admin\Support\VelmRouteRegistrar;

final class AdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ArchSchemaBuilder::class);
        $this->app->singleton(ArchTableConfigurator::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'velm-admin');

        VelmRouteRegistrar::register();
    }
}
