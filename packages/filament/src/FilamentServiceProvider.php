<?php

declare(strict_types=1);

namespace Velm\Filament;

use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
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

        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_END,
            fn (): string => view('velm-filament::partials.head-fonts')->render(),
        );
    }
}
