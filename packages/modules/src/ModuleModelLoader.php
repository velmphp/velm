<?php

declare(strict_types=1);

namespace Velm\Modules;

use Velm\Models\Model;
use Velm\Modules\Mail\MailThreadService;
use Velm\Modules\Support\ModuleNaming;
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

            self::registerModelClass($modelClass, $registry);
        }
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    public static function registerModelClass(string $modelClass, Registry $registry): void
    {
        if ($modelClass::isAbstract()) {
            $registry->registerMixin($modelClass);

            return;
        }

        if ($modelClass::isExtension()) {
            $registry->registerExtension($modelClass);
        } else {
            $registry->register($modelClass);
        }

        self::syncMailThread($registry, $modelClass);
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private static function syncMailThread(Registry $registry, string $modelClass): void
    {
        $modelName = $modelClass::isExtension()
            ? (string) $modelClass::inherit()
            : (string) $modelClass::name();

        if ($modelName === '') {
            return;
        }

        if ($registry->hasMixin($modelName, 'mail.thread')) {
            MailThreadService::registerModel($modelName);
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
    public static function ensureModelClassLoaded(string $modelClass, string $modulePath): void
    {
        if (class_exists($modelClass, false)) {
            return;
        }

        if (class_exists($modelClass)) {
            return;
        }

        $short = strrchr($modelClass, '\\');

        if ($short === false) {
            return;
        }

        $shortName = substr($short, 1);
        $modelsDir = rtrim($modulePath, '/\\').DIRECTORY_SEPARATOR.'models';

        foreach ([
            $modelsDir.DIRECTORY_SEPARATOR.ModuleNaming::classStemFromShortName($shortName).'.php',
            $modelsDir.DIRECTORY_SEPARATOR.$shortName.'.php',
        ] as $file) {
            if (is_file($file)) {
                require_once $file;

                return;
            }
        }
    }
}
