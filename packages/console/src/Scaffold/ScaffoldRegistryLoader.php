<?php

declare(strict_types=1);

namespace Velm\Console\Scaffold;

use Velm\Modules\DependencyResolver;
use Velm\Modules\ModuleDiscovery;
use Velm\Modules\ModuleModelLoader;
use Velm\Modules\ModuleSpec;
use Velm\Registry;

final class ScaffoldRegistryLoader
{
    public function loadForModule(string $moduleName, ?string $addonRoot = null): Registry
    {
        $discovery = new ModuleDiscovery;
        $specs = $discovery->discover(ModulePathResolver::moduleSearchRoots($addonRoot));

        if (! isset($specs[$moduleName])) {
            throw new \RuntimeException("Module {$moduleName} was not discovered.");
        }

        $registry = new Registry;
        $loader = new ModuleModelLoader;
        $order = (new DependencyResolver)->resolve($specs);

        foreach ($order as $spec) {
            $loader->load($spec, $registry);

            if ($spec->name === $moduleName) {
                return $registry;
            }
        }

        throw new \RuntimeException("Module {$moduleName} could not be loaded.");
    }

    public function inferModuleForModel(string $modelInput, ?string $addonRoot = null): ?string
    {
        $discovery = new ModuleDiscovery;
        $specs = $discovery->discover(ModulePathResolver::moduleSearchRoots($addonRoot));
        $modelInput = strtolower(trim($modelInput));

        foreach ($specs as $spec) {
            if ($this->specOwnsModel($spec, $modelInput)) {
                return $spec->name;
            }
        }

        if (! str_contains($modelInput, '.')) {
            $matches = [];

            foreach ($specs as $spec) {
                foreach ($spec->models as $class) {
                    if (! class_exists($class)) {
                        continue;
                    }

                    $technical = $class::name();

                    if (str_ends_with($technical, '.'.$modelInput)) {
                        $matches[$spec->name] = true;
                    }
                }
            }

            if (count($matches) === 1) {
                return array_key_first($matches);
            }
        }

        return null;
    }

    private function specOwnsModel(ModuleSpec $spec, string $modelInput): bool
    {
        foreach ($spec->models as $class) {
            if (! class_exists($class)) {
                continue;
            }

            if ($class::name() === $modelInput) {
                return true;
            }
        }

        return false;
    }
}
