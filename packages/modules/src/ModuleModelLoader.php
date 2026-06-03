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
            self::ensureModelClassLoaded($modelClass, $spec->path);

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

    /**
     * Require {module}/models/{Class}.php when Composer PSR-4 was not dumped
     * after adding a new bundled module (e.g. file_manager).
     */
    private static function ensureModelClassLoaded(string $modelClass, string $modulePath): void
    {
        if (class_exists($modelClass, false)) {
            return;
        }

        $short = strrchr($modelClass, '\\');

        if ($short === false) {
            return;
        }

        $file = rtrim($modulePath, '/\\')
            .DIRECTORY_SEPARATOR.'models'
            .DIRECTORY_SEPARATOR.substr($short, 1)
            .'.php';

        if (is_file($file)) {
            require_once $file;
        }
    }
}
