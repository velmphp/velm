<?php

namespace Velm\Core\Metadata\Lifecycle;

final class VelmLifecycle
{
    public function __construct(
        public readonly array $states,
        public readonly array $transitions,
        public readonly string $initial,
        public readonly array $terminal = [],
    ) {}
}
