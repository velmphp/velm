<?php

namespace Velm\Core\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Velm\Core\Runtime\PipelineExecutor;

abstract class RuntimeModel extends Model
{
    use PipelineExecutor;

    /**
     * Instance method interception (pipelines)
     */
    public function __call($method, $parameters)
    {
        // 1. Domain pipeline methods
        if ($this->pipelineHas($method)) {
            velm_utils()->consoleLog("The method $method is pipelined. Running pipeline...");

            return $this->runPipeline($method, $parameters);
        }

        // If the method exists on the parent Model, call it directly
        if (method_exists(parent::class, $method)) {
            velm_utils()->consoleLog("The method $method exists on parent. Calling parent method...");

            return parent::__call($method, $parameters);
        }

        // 2. Forward EVERYTHING ELSE to the query builder
        return $this->forwardCallTo(
            $this->newQuery(),
            $method,
            $parameters
        );
    }

    /**
     * Static calls must behave exactly like native Eloquent
     */
    public static function __callStatic($method, $parameters)
    {
        return (new static)->$method(...$parameters);
    }

    public function getAttribute($key)
    {
        $method = 'get'.ucfirst($key).'Attribute';

        // If accessor is pipelined → run pipeline
        if ($this->pipelineHas($method)) {
            return $this->runPipeline($method, []);
        }

        // Otherwise → native Eloquent behavior
        return parent::getAttribute($key);
    }
}
