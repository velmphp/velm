<?php

declare(strict_types=1);

namespace Velm\Modules;

use Velm\Models\Model;
use Velm\Registry;

final class ModuleModelLoader
{
    public function load(ModuleSpec $spec, Registry $registry): void
    {
        foreach ($spec->models as $modelClass) {
            if (! class_exists($modelClass)) {
                throw new \RuntimeException("Model class {$modelClass} for module {$spec->name} was not found.");
            }

            if (! is_subclass_of($modelClass, Model::class)) {
                throw new \RuntimeException("{$modelClass} must extend ".Model::class.'.');
            }

            if ($modelClass::isExtension()) {
                $registry->registerExtension($modelClass);
            } else {
                $registry->register($modelClass);
            }
        }
    }

    /**
     * @param  list<string>  $roots
     */
    public function loadInstalled(
        array $roots,
        Registry $registry,
        ModuleDiscovery $discovery,
        DependencyResolver $resolver,
        ModuleRepository $repository,
    ): void {
        $specs = $discovery->discover($roots);

        foreach ($resolver->resolve($specs) as $spec) {
            if (! $repository->isInstalled($spec->name)) {
                continue;
            }

            $this->load($spec, $registry);
        }
    }
}
