<?php

namespace Velm\Core\Runtime;

use LogicException;
use Velm\Core\Pipeline\ClassPipelineRuntime;
use Velm\Core\Pipeline\Contracts\Pipelinable;

abstract class RuntimeLogicalService implements Pipelinable
{
    public static string $logicalName = '';

    public function getLogicalName(): string
    {
        if (empty(static::$logicalName)) {
            throw new \RuntimeException("Logical service '".static::class."' does not have a logical name defined.");
        }

        return velm_utils()->formatVelmName(static::$logicalName, 'Service');
    }

    /**
     * @throws \Throwable
     */
    public function __call($method, $parameters)
    {
        $logicalName = $this->getLogicalName();
        if (ClassPipelineRuntime::hasInstancePipeline(static::class, $method)) {
            try {
                return ClassPipelineRuntime::call($this, $method, $parameters, injectSelf: false);
            } catch (\Throwable $exception) {
                velm_utils()->consoleLog(
                    "Error calling pipelined method {$method} for logical service {$logicalName}: ".$exception->getMessage()
                );
                throw $exception;
            }
        }

        throw new \BadMethodCallException("Method '{$method}' does not exist on logical service '".static::class."'.");
    }

    public static function __callStatic(string $method, array $parameters)
    {
        $allowed = ['instance', 'make'];
        if (in_array($method, $allowed)) {
            static::{$method}($parameters);
        }
        throw new LogicException(
            'Static calls are not supported on Velm logical services. '.
            'Use velm_service()->method() instead.'
        );
    }
}
