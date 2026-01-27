<?php

namespace Velm\Core\Metadata\Access;

final class VelmAccess
{
    public function __construct(
        public readonly array $read = [],
        public readonly array $write = [],
        public readonly array $delete = [],
    ) {}
}
