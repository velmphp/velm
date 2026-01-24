<?php

namespace Velm\Core\Dependencies;

final class Graph
{
    /** @var array<string, list<string>> */
    private array $edges = [];

    public function __construct(array $edges = [])
    {
        $this->edges = $edges;
    }

    public static function from(array $modules): self
    {
        $graph = new self;

        foreach ($modules as $pkg => $module) {
            $graph->addNode($pkg);

            foreach ($module->dependencies as $dependency) {
                $graph->addDependency($pkg, $dependency);
            }
        }

        return $graph;
    }

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
