<?php

namespace Velm\Core\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;
use Velm\Core\Pipeline\ClassPipelineRuntime;
use Velm\Core\Pipeline\Contracts\Pipelinable;

abstract class LogicalModel extends Model implements Pipelinable
{
    protected static string $logicalName;

    /**
     * Override table resolution to use physical model
     */
    public function getTable()
    {
        $first = $this->getExtensions()[0] ?? null;

        return $first
            ? $first->getTable()
            : parent::getTable();
    }

    public function getExtensions(): array
    {
        return velm()->registry()->pipeline()->find(static::$logicalName) ?? [];
    }

    /**
     * Return logical name
     */
    public function getLogicalName(): string
    {
        if (! static::$logicalName) {
            throw new LogicException(
                'Logical model used without logical name. '.
                'Use velm_model("Product") or Model::make().'
            );
        }

        return static::$logicalName;
    }

    /**
     * Merge properties from all extensions
     */
    protected function mergeExtensionProperties(): void
    {
        ClassPipelineRuntime::mergeProperties($this, [
            'fillable',
            'casts',
            'appends',
            'table',
            'connection',
            'primaryKey',
            'keyType',
            'incrementing',
            'timestamps',
        ]);
    }

    /* ---------------------------------
     | Instance calls → ALWAYS pipeline
     *---------------------------------*/
    public function __call($method, $parameters)
    {
        velm_utils()->consoleLog(
            "Checking pipeline for logical name {$this->getLogicalName()} and method {$method}..."
        );
        velm_utils()->consoleLog("Table name: {$this->getTable()}");

        // Merge properties first
        $this->mergeExtensionProperties();

        if (ClassPipelineRuntime::hasInstancePipeline($this->getLogicalName(), $method)) {
            velm_utils()->consoleLog(
                "Method {$method} is pipelined for logical model {$this->getLogicalName()}."
            );

            return ClassPipelineRuntime::call($this, $method, $parameters);
        }

        velm_utils()->consoleLog(
            "Method {$method} is NOT pipelined for logical model {$this->getLogicalName()}, forwarding to parent."
        );

        return parent::__call($method, $parameters);
    }

    /* ---------------------------------
     | Attribute access → ALWAYS pipeline
     *---------------------------------*/
    public function __get($key)
    {
        $this->mergeExtensionProperties();

        return ClassPipelineRuntime::callAttribute($this, $key);
    }

    public function __set($key, $value)
    {
        $this->mergeExtensionProperties();
        ClassPipelineRuntime::setAttribute($this, $key, $value);
    }

    /* ---------------------------------
     | Scopes → pipeline aware
     *---------------------------------*/
    public function scope(string $name, ...$args)
    {
        return ClassPipelineRuntime::callScope($this, $name, ...$args);
    }

    /* ---------------------------------
     | Static calls → forbidden
     *---------------------------------*/
    public static function __callStatic($method, $parameters)
    {
        throw new LogicException(
            'Static calls are not supported on Velm logical models. '.
            'Use velm_model()->method() instead.'
        );
    }
}
