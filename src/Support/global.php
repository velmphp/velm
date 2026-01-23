<?php

use Velm\Core\Support\Helpers\VelmUtils;

if (!function_exists('velm')) {
    /**
     * Get the Velm manager instance.
     */
    function velm(): \Velm\Core\Velm
    {
        return \Velm\Core\Facades\Velm::getFacadeRoot();
    }
}
if (!function_exists('velm_utils')) {
    /**
     * Get the Velm utilities instance.
     */
    function velm_utils(): VelmUtils
    {
        return app(VelmUtils::class);
    }
}

if (!function_exists('velm_factory')) {
    /**
     * Get a Velm model factory instance for the given model.
     */
    function velm_factory(string $modelClass): VelmFactory
    {
        return velm()->factory()->forModel($modelClass);
    }
}
