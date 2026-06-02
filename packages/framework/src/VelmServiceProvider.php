<?php

declare(strict_types=1);

namespace Velm\Framework;

use Illuminate\Support\ServiceProvider;
use Velm\Filament\FilamentServiceProvider;
use Velm\Modules\ModulesServiceProvider;

final class VelmServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(ModulesServiceProvider::class);
        $this->app->register(FilamentServiceProvider::class);
        $this->mergeConfigFrom(__DIR__ . '/../config/velm.php', 'velm');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/velm.php' => config_path('velm.php'),
            ], 'velm-config');
        }
    }
}
