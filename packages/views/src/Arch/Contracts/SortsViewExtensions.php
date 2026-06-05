<?php

declare(strict_types=1);

namespace Velm\Views\Arch\Contracts;

/**
 * Determines the order sibling view inherits are applied to a parent arch.
 */
interface SortsViewExtensions
{
    /**
     * @param  list<array<string, mixed>>  $extensions
     * @return list<array<string, mixed>>
     */
    public function sort(array $extensions): array;
}
