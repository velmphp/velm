<?php

namespace Velm\Core\Modules;

use Velm\Core\Contracts\VelmModuleContract;

abstract class VelmModule implements VelmModuleContract
{
    use Concerns\RegistersDomainClasses;

    // register method
    final public function register(): void
    {
        $this->registering();

        /* ====== Register Domain Classes ====== */
        $this->registerModels();
        $this->registerPolicies();
        $this->registerServices();
        $this->registerCommands();
        /* ===================================== */

        $this->registered();
    }

    public function registering(): void
    {
        // Hook for actions before registering
    }

    public function registered(): void
    {
        // Hook for actions after registering
    }

    // boot method
    final public function boot(): void
    {
        $this->booting();
        $this->booted();
    }

    public function booting(): void
    {
        // Hook for actions before booting
    }

    public function booted(): void
    {
        // Hook for actions after booting
    }

    public static function path(): string
    {
        /**
         * @var VelmModuleContract $called
         */
        $called = get_called_class();

        return \Velm::registry()->modules()->find($called::slug(), bySlug: true)?->path ?? '';
    }

    public static function version(): string
    {
        /**
         * @var VelmModuleContract $called
         */
        $called = get_called_class();

        return \Velm::registry()->modules()->find($called::slug(), bySlug: true)?->version ?? 'unknown';
    }

    public static function namespace(): string
    {
        /**
         * @var VelmModuleContract $called
         */
        $called = get_called_class();

        return \Velm::registry()->modules()->find($called::slug(), bySlug: true)?->namespace ?? '';
    }

    public static function packageName(): string
    {
        /**
         * @var VelmModuleContract $called
         */
        $called = get_called_class();

        return \Velm::registry()->modules()->find($called::slug(), bySlug: true)?->packageName ?? '';
    }

    final public static function dependencies(): array
    {
        // Get all the classes that the current module depends upon
        /**
         * @var VelmModuleContract $called
         */
        $called = get_called_class();

        return \Velm::registry()->modules()->find($called::slug(), bySlug: true)?->dependencies ?? [];
    }

    final public static function modulePath($subpath = ''): string
    {
        $basePath = static::path();

        return $subpath ? rtrim($basePath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.ltrim($subpath, DIRECTORY_SEPARATOR) : $basePath;
    }

    public function destroy(): void
    {
        // Hook for actions before destroying the module
    }

    public static function getModelsPath(string $subpath = ''): string
    {
        return static::getAppPath($subpath ? 'Models'.DIRECTORY_SEPARATOR.ltrim($subpath, DIRECTORY_SEPARATOR) : 'Models');
    }

    public static function getMigrationsPath(string $subpath = ''): string
    {
        $base = 'database'.DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR;

        return static::modulePath($base.($subpath ? ltrim($subpath, DIRECTORY_SEPARATOR) : ''));
    }

    public static function getFactoriesPath(string $subpath = ''): string
    {
        $base = 'database'.DIRECTORY_SEPARATOR.'factories'.DIRECTORY_SEPARATOR;

        return static::getAppPath($base.($subpath ? ltrim($subpath, DIRECTORY_SEPARATOR) : ''));
    }

    public static function getSeedersPath(string $subpath = ''): string
    {
        $base = 'database'.DIRECTORY_SEPARATOR.'seeders'.DIRECTORY_SEPARATOR;

        return static::modulePath($base.($subpath ? ltrim($subpath, DIRECTORY_SEPARATOR) : ''));
    }

    public static function getRoutesPath(string $subpath = ''): string
    {
        $base = 'routes'.DIRECTORY_SEPARATOR;

        return static::modulePath($base.($subpath ? ltrim($subpath, DIRECTORY_SEPARATOR) : ''));
    }

    public static function getViewsPath(string $subpath = ''): string
    {
        $base = 'resources'.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR;

        return static::modulePath($base.($subpath ? ltrim($subpath, DIRECTORY_SEPARATOR) : ''));
    }

    public static function getTranslationsPath(string $subpath = ''): string
    {
        $base = 'resources'.DIRECTORY_SEPARATOR.'lang'.DIRECTORY_SEPARATOR;

        return static::modulePath($base.($subpath ? ltrim($subpath, DIRECTORY_SEPARATOR) : ''));
    }

    public static function getConfigPath(string $subpath = ''): string
    {
        $base = 'config'.DIRECTORY_SEPARATOR;

        return static::modulePath($base.($subpath ? ltrim($subpath, DIRECTORY_SEPARATOR) : ''));
    }

    public static function getAssetsPath(string $subpath = ''): string
    {
        $base = 'resources'.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR;

        return static::modulePath($base.($subpath ? ltrim($subpath, DIRECTORY_SEPARATOR) : ''));
    }

    public static function getPoliciesPath(string $subpath = ''): string
    {
        $base = 'Policies'.DIRECTORY_SEPARATOR;

        return static::getAppPath($base.($subpath ? ltrim($subpath, DIRECTORY_SEPARATOR) : ''));
    }

    public static function getCommandsPath(string $subpath = ''): string
    {
        $base = 'Console'.DIRECTORY_SEPARATOR.'Commands'.DIRECTORY_SEPARATOR;

        return static::getAppPath($base.($subpath ? ltrim($subpath, DIRECTORY_SEPARATOR) : ''));
    }

    public static function getAppPath(string $subpath = ''): string
    {
        $base = 'app'.DIRECTORY_SEPARATOR;

        return static::modulePath($base.($subpath ? ltrim($subpath, DIRECTORY_SEPARATOR) : ''));
    }
}
