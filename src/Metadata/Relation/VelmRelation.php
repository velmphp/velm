<?php

namespace Velm\Core\Metadata\Relation;

use Velm\Core\Metadata\Types\RelationType;

final class VelmRelation
{
    public function __construct(
        public readonly string $name,
        public readonly RelationType $type,
        public readonly string $targetModel,
        public readonly bool $nullable,
        public readonly bool $owning,
        public readonly bool $aggregate,
        public readonly array $constraints,
        public readonly array $semantics,
    ) {}
}
