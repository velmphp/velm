<?php

declare(strict_types=1);

namespace Velm\Framework;

use Illuminate\Support\ServiceProvider;

final class VelmServiceProvider extends ServiceProvider
{
    public function register(): void
    {
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
