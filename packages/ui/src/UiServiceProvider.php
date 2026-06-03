<?php

declare(strict_types=1);

namespace Velm\Ui;

use Illuminate\Support\ServiceProvider;
use Velm\Ui\Forms\FormRenderer;
use Velm\Ui\Widgets\WidgetRegistry;

final class UiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(WidgetRegistry::class);
        $this->app->singleton(FormRenderer::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'velm-ui');

        $this->publishes([
            UiAssets::stylesheetPath() => public_path('css/velm/velm.css'),
            UiAssets::flowbiteScriptPath() => public_path('js/velm/flowbite.min.js'),
            UiAssets::navigationScriptPath() => public_path('js/velm/velm-nav.js'),
        ], 'velm-ui-assets');
    }
}
