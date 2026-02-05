<?php

namespace Velm\Core\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;
use Velm\Core\Pipeline\ClassPipelineRuntime;
use Velm\Core\Pipeline\Contracts\Pipelinable;

abstract class LogicalModel extends Model implements Pipelinable
{
    protected static string $logicalName;

    public function getTable()
    {
        $physical = velm()
            ->registry()
            ->pipeline()
            ->find(static::$logicalName)[0] ?? null;

        return $physical
            ? $physical->getTable()
            : parent::getTable();
    }

    public function getLogicalName(): string
    {
        if (! $this->logicalName) {
            throw new LogicException(
                'Logical model used without logical name. '.
                'Use velm_model("Product") or Model::make().'
            );
        }

        return $this->logicalName;
    }

    /* ---------------------------------
     | Instance calls → ALWAYS pipeline
     |---------------------------------*/
    public function __call($method, $parameters)
    {
        // Start with physical
        velm_utils()->consoleLog("Checking pipeline for logical name {$this->getLogicalName()} and method {$method}...");
        velm_utils()->consoleLog("Table name: {$this->getTable()}");
        if (ClassPipelineRuntime::hasInstancePipeline(
            $this->getLogicalName(),
            $method
        )) {
            velm_utils()->consoleLog("Method {$method} is pipelined for logical model {$this->getLogicalName()}.");

            return ClassPipelineRuntime::call(
                $this,
                $method,
                $parameters
            );
        }
        velm_utils()->consoleLog("Method {$method} is NOT pipelined for logical model {$this->getLogicalName()}, forwarding to parent.");

        return parent::__call($method, $parameters);
    }

    /* ---------------------------------
     | Static calls → forbidden
     |---------------------------------*/
    public static function __callStatic($method, $parameters)
    {
        throw new LogicException(
            'Static calls are not supported on Velm logical models. '.
            'Use velm_model()->method() instead.'
        );
    }
}
