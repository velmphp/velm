<?php

namespace Velm\Core\Contracts;

interface VelmCompilerContract
{
    public function compile(bool $lazy = true): void;

    public function clearCache(): void;

    public function compileSingle(string $class, ?array $definitions = null, bool $lazy = false): void;

    public function isStale(string $proxy): bool;
}
