<?php

namespace Velm\Core\Support;

use Velm\Core\Pipeline\PipelineContext;

function super(): object
{
    return PipelineContext::super();
}

if ((! function_exists('\\Velm\\Core\\Support\\model'))) {
    /**
     * Get a model instance by its logical name.
     *
     * @param  string  $logicalName  The logical name of the model.
     * @return object The model instance.
     */
    function model(string $logicalName, array $attributes = []): object
    {
        $physical = velm()->registry()->pipeline()->firstExtensionFor($logicalName);
        if (! $physical) {
            throw new \RuntimeException("No model found for logical name '{$logicalName}'");
        }
        // Return the alias
        $base = class_basename($physical);
        $fqcn = "Velm\\Models\\{$base}";
        if (! class_exists($fqcn)) {
            throw new \RuntimeException("Model class '{$fqcn}' does not exist. Make sure it is compiled and autoloaded.");
        }

        return new $fqcn($attributes);
    }
}
