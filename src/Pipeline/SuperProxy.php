<?php

namespace Velm\Core\Pipeline;

final class SuperProxy
{
    public function __construct(
        private PipelineCursor $cursor,
        private ?object $self
    ) {}

    public function __call(string $method, array $args)
    {
        return $this->cursor->next($method, $this->self, $args);
    }
}
