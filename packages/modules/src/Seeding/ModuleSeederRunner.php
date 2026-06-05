<?php

declare(strict_types=1);

namespace Velm\Modules\Seeding;

use Velm\Environment;
use Velm\Modules\DependencyResolver;
use Velm\Modules\ModuleDiscovery;
use Velm\Modules\ModuleRepository;
use Velm\Modules\ModuleSpec;

final class ModuleSeederRunner
{
    public function __construct(
        private readonly ModuleDiscovery $discovery = new ModuleDiscovery,
        private readonly DependencyResolver $resolver = new DependencyResolver,
        private readonly ModuleRepository $repository = new ModuleRepository,
    ) {}

    /**
     * @param  list<string>  $roots
     */
    public function run(Environment $env, array $roots, ?string $module = null): void
    {
        $specs = $this->discovery->discover($roots);

        if ($module !== null && $module !== '' && ! isset($specs[$module])) {
            throw new \InvalidArgumentException("Module {$module} was not discovered.");
        }

        $closure = $module !== null && $module !== ''
            ? $this->closureFor([$module], $specs)
            : null;

        foreach ($this->resolver->resolve($specs) as $spec) {
            if (! $this->repository->isInstalled($spec->name)) {
                continue;
            }

            if ($closure !== null && ! in_array($spec->name, $closure, true)) {
                continue;
            }

            $this->runSeeders($spec, $env);
        }
    }

    private function runSeeders(ModuleSpec $spec, Environment $env): void
    {
        foreach ($spec->seeders as $class) {
            if (! class_exists($class)) {
                throw new \RuntimeException("Seeder class {$class} for module {$spec->name} was not found.");
            }

            if (! method_exists($class, 'run')) {
                throw new \RuntimeException("Seeder {$class} must define a static run(Environment \$env) method.");
            }

            $class::run($env);
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

