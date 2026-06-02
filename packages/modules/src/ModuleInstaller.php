<?php

declare(strict_types=1);

namespace Velm\Modules;

final class ModuleInstaller
{
    public function __construct(
        private readonly ModuleDiscovery $discovery = new ModuleDiscovery,
        private readonly DependencyResolver $resolver = new DependencyResolver,
        private readonly ModuleRepository $repository = new ModuleRepository,
    ) {}

    /**
     * @param  list<string>  $roots
     * @return array<string, ModuleSpec>
     */
    public function discover(array $roots): array
    {
        return $this->discovery->discover($roots);
    }

    /**
     * @param  array<string, ModuleSpec>  $specs
     * @return list<ModuleSpec>
     */
    public function resolveOrder(array $specs): array
    {
        return $this->resolver->resolve($specs);
    }

    /**
     * @param  list<string>  $roots
     * @return list<array{name: string, state: string, version: string|null, summary: string, depends: string}>
     */
    public function catalog(array $roots): array
    {
        $specs = $this->discover($roots);
        $rows = [];

        foreach ($this->resolveOrder($specs) as $spec) {
            $installedVersion = $this->repository->installedVersion($spec->name);

            $rows[] = [
                'name' => $spec->name,
                'state' => $installedVersion === null ? 'uninstalled' : 'installed',
                'version' => $installedVersion ?? $spec->versionString(),
                'summary' => $spec->summary,
                'depends' => implode(', ', $spec->depends) ?: '—',
            ];
        }

        return $rows;
    }

    /**
     * @param  list<string>  $roots
     * @param  list<string>  $bootstrapModules
     */
    public function installBootstrap(array $roots, array $bootstrapModules): void
    {
        $this->installMany($roots, $bootstrapModules);
    }

    /**
     * @param  list<string>  $roots
     */
    public function install(string $moduleName, array $roots): void
    {
        $this->installMany($roots, [$moduleName]);
    }

    /**
     * @param  list<string>  $roots
     */
    public function sync(string $moduleName, array $roots): void
    {
        $specs = $this->discover($roots);

        if (! isset($specs[$moduleName])) {
            throw new \InvalidArgumentException("Module {$moduleName} was not discovered.");
        }

        if (! $this->repository->isInstalled($moduleName)) {
            throw new \RuntimeException("Module {$moduleName} is not installed. Run module:install first.");
        }

        // Phase 0: record sync as a no-op beyond confirming install state.
        // DATA / VIEW sync lands in later phases.
    }

    /**
     * @param  list<string>  $roots
     * @param  list<string>  $moduleNames
     */
    private function installMany(array $roots, array $moduleNames): void
    {
        $specs = $this->discover($roots);
        $closure = $this->closureFor($moduleNames, $specs);

        foreach ($this->resolveOrder($specs) as $spec) {
            if (! in_array($spec->name, $closure, true)) {
                continue;
            }

            if ($this->repository->isInstalled($spec->name)) {
                continue;
            }

            $this->repository->markInstalled($spec);
        }
    }

    /**
     * @param  list<string>  $moduleNames
     * @param  array<string, ModuleSpec>  $specs
     * @return list<string>
     */
    private function closureFor(array $moduleNames, array $specs): array
    {
        $pending = array_values(array_unique($moduleNames));
        $closure = [];

        while ($pending !== []) {
            $name = array_shift($pending);

            if (in_array($name, $closure, true)) {
                continue;
            }

            if (! isset($specs[$name])) {
                throw new \InvalidArgumentException("Module {$name} was not discovered.");
            }

            foreach ($specs[$name]->depends as $dependency) {
                if (! in_array($dependency, $closure, true) && ! in_array($dependency, $pending, true)) {
                    $pending[] = $dependency;
                }
            }

            $closure[] = $name;
        }

        return $closure;
    }
}
