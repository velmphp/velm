<?php

namespace Velm\Core\Pipeline;

final class PipelineCursor
{
    private int $index = 0;

    public function __construct(private array $handlers) {}

    public function next(string $method, object $self, array $args)
    {
        if (! isset($this->handlers[$this->index])) {
            return null;
        }

        $handler = $this->handlers[$this->index++];

        return $handler->$method($self, ...$args);
    }
}
