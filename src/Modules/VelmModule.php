<?php

namespace Velm\Core\Modules;

use Velm\Core\Contracts\VelmModuleContract;

abstract class VelmModule implements VelmModuleContract
{
    // register method
    final public function register(): void
    {
        $this->registering();
        // Not abstract since the slug method has already been implemented in the child class
        $child = get_called_class();
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
        return \Velm::registry()->modules()->resolvePath($called::slug());
    }

    public static function version(): string
    {
        /**
         * @var VelmModuleContract $called
         */
        $called = get_called_class();
        return \Velm::registry()->modules()->find($called::slug())?->version ?? 'unknown';
    }

    public static function namespace(): string
    {
        /**
         * @var VelmModuleContract $called
         */
        $called = get_called_class();
        return \Velm::registry()->modules()->resolveNamespace($called::slug());
    }

    public static function packageName(): string
    {
        /**
         * @var VelmModuleContract $called
         */
        $called = get_called_class();
        return \Velm::registry()->modules()->resolvePackageName($called::slug());
    }

    final public static function dependencies(): array
    {
        // Get all the classes that the current module depends upon
        /**
         * @var VelmModuleContract $called
         */
        $called = get_called_class();
        return \Velm::registry()->modules()->resolveDependencies($called::slug());
    }
}
