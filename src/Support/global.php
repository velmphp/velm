<?php

use Velm\Core\Runtime\SuperStack;
use Velm\Core\Support\Helpers\VelmUtils;

if (! function_exists('velm')) {
    /**
     * Get the Velm manager instance.
     */
    function velm(): \Velm\Core\Velm
    {
        return \Velm\Core\Facades\Velm::getFacadeRoot();
    }
}
if (! function_exists('velm_utils')) {
    /**
     * Get the Velm utilities instance.
     */
    function velm_utils(): VelmUtils
    {
        return app(VelmUtils::class);
    }
}

if (! function_exists('velm_base_path')) {
    // get the base path
    function velm_base_path(string $package, string $path = ''): string
    {
        $module = velm()->registry()->modules()->findOrFail($package);

        return $module->entryPoint::modulePath($path);
    }
}

if (! function_exists('velm_app_path')) {
    // get the app path
    function velm_app_path(string $package, string $path = ''): string
    {
        $module = velm()->registry()->modules()->findOrFail($package);

        return $module->entryPoint::getAppPath($path);
    }
}

if (! function_exists('super')) {
    function super(...$args): mixed
    {
        return SuperStack::next(...$args);
    }
}
