<?php

namespace Velm\Core\Dependencies;

use RuntimeException;

final class UninstallResolver
{
    public function __construct(
        private readonly ReverseGraph $graph
    ) {}

    /**
     * @return list<string>
     */
    public function resolve(string $package, bool $cascade = true): array
    {
        $dependents = $this->graph->dependentsOf($package);

        if (! $cascade && ! empty($dependents)) {
            $dependentsList = implode(', ', $dependents);
            throw new RuntimeException("Cannot uninstall package '{$package}' because the following packages depend on it: {$dependentsList}");
        }

        return $cascade
            ? $this->graph->cascadeFrom($package)
            : [$package];
    }
}
