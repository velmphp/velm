<?php

declare(strict_types=1);

namespace Velm\Views;

use Illuminate\Support\ServiceProvider;
use Velm\Views\Data\DataFileLoader;
use Velm\Views\Sync\ViewSynchronizer;

final class ViewsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ViewRegistry::class);
        $this->app->singleton(DataFileLoader::class);
        $this->app->singleton(ViewSynchronizer::class);
    }
}
