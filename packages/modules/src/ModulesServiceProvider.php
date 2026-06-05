<?php

declare(strict_types=1);

namespace Velm\Modules;

use Illuminate\Support\ServiceProvider;
use Velm\Modules\Autoload\ModuleClassAutoloader;
use Velm\Views\Arch\Contracts\SortsViewExtensions;
use Velm\Views\Contracts\SyncsModuleViews;

final class ModulesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ModuleInstaller::class);
        $this->app->singleton(SortsViewExtensions::class, ModuleDependencyViewExtensionSorter::class);
        $this->app->singleton(SyncsModuleViews::class, ModuleViewSync::class);
    }

    public function boot(): void
    {
        ModuleClassAutoloader::register([
            'Velm\\Modules\\' => [dirname(__DIR__).'/modules'],
            'Addons\\' => static function (): array {
                if (! function_exists('config')) {
                    return [];
                }

                /** @var list<string>|null $paths */
                $paths = config('velm.addon_autoload_paths');

                return is_array($paths) ? $paths : [];
            },
        ]);

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
