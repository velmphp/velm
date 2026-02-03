<?php

namespace Velm\Core\Runtime;

trait PipelineExecutor
{
    public function callPipeline(string $method, array $args)
    {
        $stack = \Velm::registry()->runtime()::$pipelines[static::class][$method] ?? [];

        return SuperStack::invoke($this, $stack, $method, $args);
    }

    public function __call($method, $args)
    {
        velm_utils()->consoleLog("Calling method: {$method}");

        // What if the pipeline stack is empty and the method exists natively on the parent Eloquent Model class, e.g getModel()?
        return $this->callPipeline($method, $args);
    }
}
