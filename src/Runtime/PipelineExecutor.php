<?php

namespace Velm\Core\Runtime;

trait PipelineExecutor
{
    public function __call($method, $parameters)
    {
        $class = static::class;
        $pipelines = velm()->registry()->runtime()::$pipelines[$class] ?? [];

        // 1️⃣ Method not pipelined → call parent directly
        if (! isset($pipelines[$method])) {
            velm_utils()->consoleLog("PipelineExecutor::__call forwarding method: $method to parent");

            return $this->callParent($method, $parameters);
        }

        // 2️⃣ Pipeline exists → invoke
        return SuperStack::invoke(
            $this,
            $pipelines[$method],
            $method,
            $parameters
        );
    }

    protected function callParent(string $method, array $parameters)
    {
        // Correct Eloquent-native fallback
        return $this->forwardCallTo(
            $this->newQuery(),
            $method,
            $parameters
        );
    }

    protected function pipelineHas(string $method): bool
    {
        $class = static::class;
        $pipelines = velm()->registry()->runtime()::$pipelines[$class] ?? [];

        return isset($pipelines[$method]);
    }

    protected function runPipeline(string $method, array $parameters)
    {
        $class = static::class;
        $pipelines = velm()->registry()->runtime()::$pipelines[$class] ?? [];

        return SuperStack::invoke(
            $this,
            $pipelines[$method],
            $method,
            $parameters
        );
    }
}
