<?php

declare(strict_types=1);

namespace Velm\Views;

use Illuminate\Support\ServiceProvider;
use Velm\Views\Arch\ArchResolver;
use Velm\Views\Data\DataFileLoader;
use Velm\Views\Sync\MenuSynchronizer;
use Velm\Views\Sync\ViewSynchronizer;

final class ViewsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ArchResolver::class);
        $this->app->singleton(ViewRegistry::class);
        $this->app->singleton(DataFileLoader::class);
        $this->app->singleton(ViewSynchronizer::class);
        $this->app->singleton(MenuSynchronizer::class);
        $this->app->singleton(MenuRegistry::class);
    }
}
