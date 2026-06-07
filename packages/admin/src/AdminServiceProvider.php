<?php

declare(strict_types=1);

namespace Velm\Admin;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Velm\Admin\Arch\ArchSchemaBuilder;
use Velm\Admin\Arch\ArchTableConfigurator;
use Velm\Admin\Arch\GraphDataBuilder;
use Velm\Admin\Arch\KanbanBoardBuilder;
use Velm\Admin\Arch\PivotDataBuilder;
use Velm\Admin\Arch\PivotGridBuilder;
use Velm\Admin\Arch\ViewFieldCatalog;
use Velm\Admin\Support\AnalyticsViewSwitcher;
use Velm\Admin\Support\VelmRouteRegistrar;
use Velm\Framework\Http\Middleware\BindVelmEnvironment;

final class AdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ArchSchemaBuilder::class);
        $this->app->singleton(ArchTableConfigurator::class);
        $this->app->singleton(KanbanBoardBuilder::class);
        $this->app->singleton(GraphDataBuilder::class);
        $this->app->singleton(PivotGridBuilder::class);
        $this->app->singleton(PivotDataBuilder::class);
        $this->app->singleton(ViewFieldCatalog::class);
        $this->app->singleton(AnalyticsViewSwitcher::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'velm-admin');

        VelmRouteRegistrar::register();

        Route::middleware(['api', BindVelmEnvironment::class])
            ->prefix('api')
            ->group(__DIR__.'/../routes/analytics-api.php');
    }
}
