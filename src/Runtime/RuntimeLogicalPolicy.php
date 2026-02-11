<?php

namespace Velm\Core\Runtime;

use Velm\Core\Pipeline\ClassPipelineRuntime;
use Velm\Core\Pipeline\Contracts\Pipelinable;

class RuntimeLogicalPolicy implements Pipelinable
{
    public static string $logicalName = '';

    final public function getLogicalName(): string
    {
        if (empty(static::$logicalName)) {
            throw new \RuntimeException("Logical Policy '".static::class."' does not have a logical name defined.");
        }

        return velm_utils()->formatVelmName(static::$logicalName, 'Policy');
    }

    public function __call($method, $parameters)
    {
        if (ClassPipelineRuntime::hasInstancePipeline($logicalName = $this->getLogicalName(), $method)) {
            try {
                return ClassPipelineRuntime::call($this, $method, $parameters);
            } catch (\Throwable $exception) {
                velm_utils()->consoleLog(
                    "Error calling pipelined method {$method} for logical model {$logicalName}: ".$exception->getMessage()
                );

                return false;
            }
        }

        throw new \BadMethodCallException("Method '{$method}' does not exist on logical policy '".static::class."'.");
    }
}
