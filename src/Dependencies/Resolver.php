<?php

namespace Velm\Core\Dependencies;

use Illuminate\Contracts\Container\CircularDependencyException;

final class Resolver
{
    private array $graph;

    private array $visited = [];

    private array $visiting = [];

    private array $result = [];

    public function __construct(Graph $graph)
    {
        $this->graph = $graph->edges();
    }

    public function resolve(): array
    {
        // reset state (important!)
        $this->visited = [];
        $this->visiting = [];
        $this->result = [];
        $nodes = array_keys($this->graph);
        sort($nodes, SORT_STRING);

        foreach ($nodes as $node) {
            if (! isset($this->visited[$node])) {
                $this->visit($node);
            }
        }

        return $this->result;
    }

    /** Resolve only $package and its dependencies
     * @throws \Exception
     */
    public function resolveFor(string $package): array
    {
        if (! isset($this->graph[$package])) {
            throw new \Exception("Unknown module: {$package}");
        }

        // reset state (important!)
        $this->visited = [];
        $this->visiting = [];
        $this->result = [];

        $this->visit($package);

        return $this->result;
    }

    private function visit(string $node): void
    {
        if (isset($this->visiting[$node])) {
            throw new CircularDependencyException("Circular dependency: {$node}");
        }

        if (isset($this->visited[$node])) {
            return;
        }

        $this->visiting[$node] = true;

        $deps = $this->graph[$node] ?? [];
        sort($deps, SORT_STRING);

        foreach ($deps as $dep) {
            $this->visit($dep);
        }

        unset($this->visiting[$node]);
        $this->visited[$node] = true;
        $this->result[] = $node;
    }
}
