<?php

declare(strict_types=1);

namespace Velm\Modules;

final class DependencyResolver
{
    private const int UNVISITED = 0;

    private const int VISITING = 1;

    private const int VISITED = 2;

    /**
     * @param  array<string, ModuleSpec>  $specs
     * @return list<ModuleSpec>
     */
    public function resolve(array $specs): array
    {
        $state = array_fill_keys(array_keys($specs), self::UNVISITED);
        $order = [];

        foreach (array_keys($specs) as $name) {
            $this->visit($name, $specs, $state, $order, []);
        }

        return $order;
    }

    /**
     * @param  array<string, ModuleSpec>  $specs
     * @param  array<string, int>  $state
     * @param  list<ModuleSpec>  $order
     * @param  list<string>  $stack
     */
    private function visit(string $name, array $specs, array &$state, array &$order, array $stack): void
    {
        if ($state[$name] === self::VISITED) {
            return;
        }

        if ($state[$name] === self::VISITING) {
            $cycleStart = array_search($name, $stack, true);
            $cycle = $cycleStart === false
                ? array_merge($stack, [$name])
                : array_merge(array_slice($stack, $cycleStart), [$name]);

            throw new \RuntimeException('Module dependency cycle: '.implode(' -> ', $cycle));
        }

        $state[$name] = self::VISITING;

        foreach ($specs[$name]->depends as $dependency) {
            if (! isset($specs[$dependency])) {
                throw new \RuntimeException(sprintf(
                    "Module %s depends on %s which was not discovered.",
                    $name,
                    $dependency,
                ));
            }

            $this->visit($dependency, $specs, $state, $order, [...$stack, $name]);
        }

        $state[$name] = self::VISITED;
        $order[] = $specs[$name];
    }
}
