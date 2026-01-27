<?php

namespace Velm\Core\Compiler\Concerns;

use LogicException;

trait HasVelmPipelines
{
    protected static array $_velmPipelines = [];

    /**
     * Stores boot status for each subclass.
     * Ensures that each Velm proxy model boots independently.
     *
     * @var array<class-string,bool>
     */
    protected static array $_velmBootedPerClass = [];

    /**
     * Ensure pipelines are booted for this class.
     * This guarantees every instance, relationship, and scope has pipelines initialized.
     */
    protected static function bootVelmIfNotBooted(): void
    {
        $class = static::class;

        if (! empty(static::$_velmBootedPerClass[$class])) {
            // Already booted
            return;
        }
        // Call the subclass-implemented pipelines
        static::bootVelmPipelines();

        static::$_velmBootedPerClass[$class] = true;
    }

    /**
     * Implemented by generated proxies
     */
    protected static function bootVelmPipelines(): void
    {
        // to be implemented by generated proxies
    }

    /**
     * Accessor / pipeline wrapper helper
     */
    protected function invokePipeline(string $method, array $parameters = []): mixed
    {
        static::bootVelmIfNotBooted();

        if (empty(static::$_velmPipelines[$method])) {
            throw new LogicException(sprintf(
                '%s: Velm pipeline for "%s" not found',
                static::class,
                $method
            ));
        }

        $pipeline = static::$_velmPipelines[$method];
        $index = 0;
        $args = $parameters;
        $next = function () use (&$next, &$pipeline, &$index, &$args) {
            if (! isset($pipeline[$index])) {
                return null;
            }
            $current = $pipeline[$index++];
            $current = $current->bindTo($this, static::class);

            return $current($next, ...$args);
        };

        return $next();
    }
}
