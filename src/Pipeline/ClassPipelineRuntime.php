<?php

namespace Velm\Core\Pipeline;

use BadMethodCallException;
use ReflectionMethod;
use RuntimeException;
use Velm\Core\Pipeline\Contracts\Pipelinable;

final class ClassPipelineRuntime
{
    /* -----------------------------
     | Instance method pipeline
     *----------------------------- */
    public static function call(Pipelinable $self, string $method, array $args = [], bool $injectSelf = true)
    {
        $logicalName = $self->getLogicalName();
        $extensions = ClassPipelineRegistry::extensionsFor($logicalName);

        $extensions = array_reverse($extensions);

        $handlers = array_values(array_filter($extensions, function ($h) use ($method) {
            if (! method_exists($h, $method)) {
                return false;
            }
            $ref = new ReflectionMethod($h, $method);

            return $ref->isPublic() || $ref->isProtected();
        }));

        if (empty($handlers)) {
            throw new BadMethodCallException("Method {$method} not found in pipeline for {$logicalName}");
        }

        $cursor = new PipelineCursor($handlers);
        $super = new SuperProxy($cursor, $injectSelf ? $self : null);

        PipelineContext::push($super);
        try {
            return $cursor->next($method, $injectSelf ? $self : null, $args);
        } finally {
            PipelineContext::pop();
        }
    }

    public static function callByLogicalName(string $logicalName, string $method, array $args = [], bool $injectSelf = true)
    {
        $extensions = ClassPipelineRegistry::extensionsFor($logicalName);
        if (empty($extensions)) {
            throw new \RuntimeException("No registered classes for logical name {$logicalName}");
        }

        if ($injectSelf) {
            // Pick first registered instance as $self
            /**
             * @var Pipelinable $self
             */
            $self = $extensions[0];

            return self::call($self, $method, $args);
        } else {
            // for policies, just pass $args
            $cursor = new PipelineCursor($extensions);
            $extensions = array_reverse($extensions);
            // Check for the handlers and method existence before starting the pipeline
            // Filter ONLY handlers that implement the method
            $handlers = array_values(array_filter($extensions, function ($ext) use ($method) {
                if (! method_exists($ext, $method)) {
                    return false;
                }

                $ref = new \ReflectionMethod($ext, $method);

                return $ref->isPublic() || $ref->isProtected();
            }));

            if (empty($handlers)) {
                throw new \BadMethodCallException(
                    "Policy method {$method} not defined for logical policy {$logicalName}"
                );
            }

            // Use first valid handler as $self
            $self = $handlers[0];

            return self::call($self, $method, $args);
        }
    }

    /* -----------------------------
     | Static pipeline
     *----------------------------- */
    public static function callStatic(string $logicalName, string $method, array $args = [])
    {
        $handlers = ClassPipelineRegistry::staticExtensionsFor($logicalName);

        if (empty($handlers)) {
            throw new RuntimeException("No registered static classes for logical name {$logicalName}");
        }

        $cursor = new class($handlers, $method, $args)
        {
            private int $index = 0;

            public function __construct(private array $handlers, private string $method, private array $args) {}

            public function next()
            {
                if (! isset($this->handlers[$this->index])) {
                    return null;
                }
                $class = $this->handlers[$this->index++];

                return $class::{$this->method}(...$this->args);
            }
        };

        $super = new class($cursor)
        {
            public function __construct(private $cursor) {}

            public function __call($method, $args)
            {
                return $this->cursor->next();
            }
        };

        PipelineContext::push($super);
        try {
            return $cursor->next();
        } finally {
            PipelineContext::pop();
        }
    }

    /* -----------------------------
     | Pipeline existence
     *----------------------------- */
    public static function hasInstancePipeline(string $fqcn, string $method): bool
    {
        $logicalName = class_basename($fqcn);
        $extensions = ClassPipelineRegistry::extensionsFor($logicalName);

        foreach ($extensions as $ext) {
            if (! method_exists($ext, $method)) {
                continue;
            }
            $ref = new ReflectionMethod($ext, $method);
            if ($ref->isPublic() || $ref->isProtected()) {
                return true;
            }
        }

        return false;
    }

    public static function hasScope(string $logicalName, string $scope): bool
    {
        $method = 'scope'.ucfirst($scope);

        foreach (ClassPipelineRegistry::extensionsFor($logicalName) as $ext) {
            if (method_exists($ext, $method)) {
                return true;
            }
        }

        return false;
    }

    public static function callScope(
        Pipelinable $self,
        string $scope,
        array $args
    ) {
        $method = 'scope'.ucfirst($scope);

        // First argument must be the query builder
        $query = array_shift($args);

        return self::call(
            $self,
            $method,
            array_merge([$query], $args)
        );
    }
}
