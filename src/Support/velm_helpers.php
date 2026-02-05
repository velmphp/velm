<?php

namespace Velm\Core\Support;

use Velm\Core\Eloquent\PipelineModel;
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
    function model(string $logicalName): object
    {
        // Make a concrete of the abstract PipelineModel class, then instantiate it and return the instance.
        // The concrete should have a static property $_velm_name set to $logicalName, and should be registered in the ClassPipelineRegistry with the same logical name.
        $class = get_class(new class extends PipelineModel
        {
            public static string $velm_name = '';
        });
        $class::$velm_name = $logicalName;

        return new $class;
    }
}
