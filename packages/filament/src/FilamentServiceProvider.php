<?php

declare(strict_types=1);

namespace Velm\Filament;

use Illuminate\Support\ServiceProvider;
use Velm\Filament\Arch\ArchSchemaBuilder;
use Velm\Filament\Arch\ArchTableConfigurator;

final class FilamentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ArchSchemaBuilder::class);
        $this->app->singleton(ArchTableConfigurator::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'velm-filament');
    }
}
