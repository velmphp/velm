<?php

use Velm\Core\Facades\Velm;
use Velm\Core\Pipeline\PipelineContext;
use Velm\Core\Support\Helpers\VelmUtils;

if (! function_exists('velm')) {
    /**
     * Get the Velm manager instance.
     */
    function velm(): \Velm\Core\Velm
    {
        return Velm::getFacadeRoot();
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
    function super(): object
    {
        return PipelineContext::super();
    }
}

if (! function_exists('velm_model')) {
    function velm_model(string $logicalName, array $attributes = []): object
    {
        $logicalName = velm_utils()->formatVelmName($logicalName, 'Model');
        $physical = velm()->registry()->pipeline()->firstExtensionFor($logicalName);
        if (! $physical) {
            throw new RuntimeException("No model found for logical name '{$logicalName}'");
        }
        // Return the alias
        $base = velm_utils()->getBaseClassName($logicalName);
        $fqcn = "Velm\\Models\\{$base}";
        if (! class_exists($fqcn)) {
            throw new RuntimeException("Model class '{$fqcn}' does not exist. Make sure it is compiled and autoloaded.");
        }

        return new $fqcn($attributes);
    }
}
if (! function_exists('velm_service')) {
    function velm_service(string $logicalName): object
    {
        $baseName = velm_utils()->getBaseClassName($logicalName, 'Service');
        $fqcn = "Velm\\Services\\$baseName";
        if (! class_exists($fqcn)) {
            throw new RuntimeException("Service class '{$fqcn}' does not exist. Make sure it is compiled and autoloaded.");
        }

        return app($fqcn);
    }
}
