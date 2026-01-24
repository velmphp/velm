<?php

namespace Velm\Core\Dependencies;

use Illuminate\Contracts\Container\BindingResolutionException;

final class ReverseGraph
{
    /** @var array<string, list<string>> */
    private array $edges = [];

    /**
     * @throws BindingResolutionException
     */
    public static function from(Graph $graph): self
    {
        $edges = [];

        foreach ($graph->edges() as $package => $dependencies) {
            foreach ($dependencies as $dependency) {
                // Add it only if it is installed
                if (velm()->registry()->modules()->isInstalled($package, velm()->tenant())) {
                    $edges[$dependency][] = $package;
                }
            }
        }

        return new self($edges);
    }

    /**
     * @param  array<string, list<string>>  $edges
     */
    private function __construct(array $edges)
    {
        $this->edges = $edges;
    }

    /** @return list<string> */
    public function dependentsOf(string $package): array
    {
        return $this->edges[$package] ?? [];
    }

    /**
     * Resolve all dependents in correct uninstall order
     *
     * @return list<string>
     */
    public function cascadeFrom(string $package): array
    {
        $visited = [];
        $result = [];

        $visit = function (string $pkg) use (&$visit, &$visited, &$result) {
            if (isset($visited[$pkg])) {
                return;
            }

            $visited[$pkg] = true;

            foreach ($this->dependentsOf($pkg) as $dependent) {
                $visit($dependent);
            }

            $result[] = $pkg;
        };

        $visit($package);

        return $result;
    }
}
