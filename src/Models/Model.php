<?php

namespace Velm\Core\Models;

use Velm\Core\Eloquent\PipelineModel;
use Velm\Core\Pipeline\ClassPipelineRuntime;

final class Model extends PipelineModel
{
    protected ?string $logicalName = null;

    protected ?object $physical = null;

    /* ---------------------------------
     | Construction (Laravel-safe)
     |---------------------------------*/
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }

    /**
     * Velm entry point
     */
    public static function make(string $logicalName, array $attributes = []): self
    {
        $instance = new self($attributes);
        $instance->logicalName = $logicalName;

        // Configure table from first physical model (definition only)
        $physical = velm()->registry()->pipeline()->find($logicalName);
        if (empty($physical)) {
            throw new \InvalidArgumentException(
                "No physical model registered for logical name: {$logicalName}"
            );
        }

        if ($physical[0] instanceof \Illuminate\Database\Eloquent\Model) {
            $instance->setTable($physical[0]->getTable());
        }
        $instance->physical = $physical[0] ?? null;

        return $instance;
    }

    /**
     * CRITICAL: called by Eloquent internally
     */
    public function newInstance($attributes = [], $exists = false): self
    {
        $model = parent::newInstance($attributes, $exists);

        // Preserve logical name across hydration
        $model->logicalName = $this->getLogicalName();
        $model->physical = $this->physical;

        return $model;
    }

    public function getLogicalName(): string
    {
        if (! $this->logicalName) {
            throw new \LogicException(
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
        if (ClassPipelineRuntime::hasInstancePipeline(
            $this->getLogicalName(),
            $method
        )) {
            return ClassPipelineRuntime::call(
                $this,
                $method,
                $parameters
            );
        }

        return parent::__call($method, $parameters);
    }

    /* ---------------------------------
     | Static calls → forbidden
     |---------------------------------*/
    public static function __callStatic($method, $parameters)
    {
        throw new \LogicException(
            'Static calls are not supported on Velm logical models. '.
            'Use velm_model()->method() instead.'
        );
    }
}
