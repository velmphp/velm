<?php

namespace Velm\Core\Pipeline;

use Velm\Core\Pipeline\Contracts\Pipelinable;

final class ClassPipelineRuntime
{
    /**
     * Instance pipeline
     */
    public static function call(Pipelinable $self, string $method, array $args = [])
    {
        velm_utils()->consoleLog('runtime:call invoked from '.get_class($self)." for method {$method}");
        $logicalName = $self->getLogicalName();
        $extensions = ClassPipelineRegistry::extensionsFor($logicalName);

        // Last defined should be first called
        $extensions = array_reverse($extensions);

        // Only public/protected methods
        $handlers = array_values(array_filter($extensions, function ($h) use ($method) {
            if (! method_exists($h, $method)) {
                return false;
            }
            $ref = new \ReflectionMethod($h, $method);

            return $ref->isPublic() || $ref->isProtected();
        }));

        if (empty($handlers)) {
            throw new \BadMethodCallException("Method {$method} not found in pipeline for {$logicalName}");
        }
        velm_utils()->consoleLog('runtime:call found handlers: '.count($handlers));
        $cursor = new PipelineCursor($handlers);
        $super = new SuperProxy($cursor, $self);

        PipelineContext::push($super);
        try {
            return $cursor->next($method, $self, $args);
        } finally {
            PipelineContext::pop();
        }
    }

    /**
     * Instance pipeline by logical name (no base app instance needed)
     */
    public static function callByLogicalName(string $logicalName, string $method, array $args = [])
    {
        $extensions = ClassPipelineRegistry::extensionsFor($logicalName);
        if (empty($extensions)) {
            throw new \RuntimeException("No registered classes for logical name {$logicalName}");
        }

        $self = $extensions[0]; // first registered instance

        return self::call($self, $method, $args);
    }

    /**
     * Static pipeline
     */
    public static function callStatic(string $logicalName, string $method, array $args = [])
    {
        $handlers = ClassPipelineRegistry::staticExtensionsFor($logicalName);

        if (empty($handlers)) {
            throw new \RuntimeException("No registered static classes for logical name {$logicalName}");
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

    /**
     * @param  class-string  $fqcn
     */
    public static function hasInstancePipeline(string $fqcn, string $method): bool
    {
        $logicalName = class_basename($fqcn);
        $extensions = ClassPipelineRegistry::extensionsFor($logicalName);

        foreach ($extensions as $ext) {
            if (! method_exists($ext, $method)) {
                continue;
            }

            $ref = new \ReflectionMethod($ext, $method);
            if ($ref->isPublic() || $ref->isProtected()) {
                return true;
            }
        }

        return false;
    }
}
