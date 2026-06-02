<?php

declare(strict_types=1);

namespace Velm\Migrations;

final class ColumnDefinition
{
    public function __construct(
        public readonly string $name,
        public readonly string $sqlType,
        public readonly bool $nullable = true,
        public readonly mixed $default = null,
    ) {}
}
