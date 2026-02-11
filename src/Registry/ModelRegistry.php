<?php

namespace Velm\Core\Registry;

use Illuminate\Support\Facades\Gate;
use Velm\Core\Domain\VelmModel;
use Velm\Core\Domain\VelmPolicy;
use Velm\Core\Runtime\RuntimeLogicalModel;

use function Pest\Laravel\get;

class ModelRegistry
{
    private array $_models = [];

    private array $_definitionsMap = [];

    /**
     * Register a model under its corresponding module
     */
    final public function register($models, string $package): void
    {
        $instances = collect((array) $models)->map(function ($model) {
            return new $model;
        })->all();
        foreach ($instances as $instance) {
            $model = get_class($instance);
            $this->_models[$package][$instance->getLogicalName()] = $model;
            // register to the pipeline registry as well
            velm()->registry()->pipeline()::register($instance);
            velm()->registry()->pipeline()::registerStatic($model, $instance->getLogicalName());
            // register policy for the model
            $policy = Gate::getPolicyFor($model);
            if ($policy && is_subclass_of($policy, VelmPolicy::class)) {
                velm()->registry()->pipeline()::register($policy, $policy->getLogicalName());
                velm()->registry()->pipeline()::registerStatic(get_class($policy), $policy->getLogicalName());

                // create the policy alias
                $policyBaseName = $policy->getLogicalName();
                $policyFqcn = "Velm\\Policies\\$policyBaseName";
                if (! class_exists($policyFqcn)) {
                    eval("
                        namespace Velm\Policies;
                        use Velm\Core\Runtime\RuntimeLogicalPolicy;
                        final class {$policyBaseName} extends RuntimeLogicalPolicy {
                            public static string \$logicalName = '$policyBaseName';
                        }
                    ");
                }
            }
            // Runtime Model Alias
            // Create a runtime alias
            $logicalName = $instance->getLogicalName();
            // Remove the 'Model' suffix from the logical name if it exists, to get the base name for the runtime model
            $baseName = velm_utils()->getBaseClassName($logicalName);
            /**
             * @var class-string<RuntimeLogicalModel> $fqcn
             */
            $fqcn = "Velm\\Models\\$baseName";
            if (class_exists($fqcn)) {
                continue;
            }
            eval("
                namespace Velm\Models;
                use Velm\Core\Runtime\RuntimeLogicalModel;
                final class {$baseName} extends RuntimeLogicalModel {
                    public static string \$logicalName = '$logicalName';
                }
            ");
        }
    }

    /**
     * Get all registered models
     *
     * @return array<string, array<class-string<VelmModel>>> An associative array where keys are package names and values are arrays of model FQCNs
     */
    final public function all(): array
    {
        return $this->_models;
    }

    final public function definitionsMap(): array
    {
        if (! empty($this->_definitionsMap)) {
            return $this->_definitionsMap;
        }
        $definitions = [];
        foreach ($this->_models as $package => $packageModels) {
            foreach ($packageModels as $name => $model) {
                $definitions[$name][] = $model;
            }
        }
        $this->_definitionsMap = $definitions;

        return $this->_definitionsMap;
    }

    /**
     * Get all extensions instances for a specific model logical Name
     *
     * @param  string  $modelName  The logical name of the model
     * @return array<class-string<VelmModel>> An array of model FQCNs that match the given logical name
     */
    final public function definitions(string $modelName): array
    {
        $modelName = velm_utils()->formatVelmName($modelName, 'Model');
        $definitions = [];
        foreach ($this->_models as $packageModels) {
            foreach ($packageModels as $name => $model) {
                if ($name === $modelName) {
                    $definitions[] = $model;
                }
            }
        }

        return $definitions;
    }

    public function discoverForPackage(string $package, bool $autoRegister = false): array
    {
        $module = velm()->registry()->modules()->findOrFail($package);
        // scan the app path for classes that are subclasses of Velm\Core\Domain\VelmModel
        $models = [];
        $appPath = $module->entryPoint::getAppPath();

        if (is_dir($appPath)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($appPath)
            );
            foreach ($iterator as $file) {
                /** @var \SplFileInfo $file */
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $class = $module->entryPoint::getNamespaceFromPath($file->getRealPath());
                    if (class_exists($class) && is_subclass_of($class, VelmModel::class)) {
                        $models[] = $class;
                    }
                }
            }
        }
        if ($autoRegister) {
            $this->register($models, $package);
        }

        return $models;
    }
}
