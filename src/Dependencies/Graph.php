<?php

namespace Velm\Core\Dependencies;

final class Graph
{
    /** @var array<string, list<string>> */
    private array $edges = [];

    public function addNode(string $package): void
    {
        $this->edges[$package] ??= [];
    }

    public function addDependency(string $package, string $dependsOn): void
    {
        $this->addNode($package);
        $this->addNode($dependsOn);

        $this->edges[$package][] = $dependsOn;
    }

    /** @return array<string, list<string>> */
    public function edges(): array
    {
        return $this->edges;
    }
}
