<?php

namespace Velm\Core\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Velm\Core\Pipeline\ClassPipelineRuntime;
use Velm\Core\Pipeline\Contracts\Pipelinable;

abstract class PipelineModel extends Model implements Pipelinable
{
    final public static function call(object $instance, string $method, array $parameters)
    {
        return $instance->_call($method, $parameters);
    }

    public function __call($method, $parameters)
    {
        velm_utils()->consoleLog("[PipelineModel] __call invoked for method: $method");
        // 1️⃣ If pipeline has this method, use it
        if (ClassPipelineRuntime::hasInstancePipeline(static::class, $method)) {
            velm_utils()->consoleLog('[PipelineModel] Calling pipeline method: '.static::class.'::'.$method);

            return ClassPipelineRuntime::call($this, $method, $parameters);
        }

        // 2️⃣ If method exists on $physical class or parent, call it normally
        if (method_exists($this, $method)) {
            return $this->{$method}(...$parameters);
        }

        // 3️⃣ Final fallback: Eloquent magic (__call on Model)
        return parent::__call($method, $parameters);
    }
}
