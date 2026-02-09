<?php

namespace Velm\Core\Registry;

use Illuminate\Support\Facades\Gate;
use Velm\Core\Compiler\DomainType;
use Velm\Core\Domain\BaseModel;
use Velm\Core\Runtime\RuntimeLogicalModel;

class ModelRegistry
{
    private array $_models = [];

    private array $_definitionsMap = [];

    private array $_proxies = [];

    /**
     * Register a model under its corresponding module
     */
    final public function register($models, string $package): void
    {
        foreach ((array) $models as $model) {
            $this->_models[$package][class_basename($model)] = $model;
            // register to the pipeline registry as well
            $instance = new $model;
            velm()->registry()->pipeline()::register(new $model);
            velm()->registry()->pipeline()::registerStatic($model);
            // register policy for the model
            $policy = Gate::getPolicyFor($model);
            if ($policy) {
                velm()->registry()->pipeline()::register($policy);
                velm()->registry()->pipeline()::registerStatic(get_class($policy));

                // create the policy alias
                $policyBaseName = class_basename($policy);
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
            $baseName = class_basename($instance);
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
                    public static string \$logicalName = '$baseName';
                }
            ");
        }
    }

    /**
     * Get all registered models
     *
     * @return array<string, array<class-string<BaseModel>>> An associative array where keys are package names and values are arrays of model FQCNs
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
     * @return array<class-string<BaseModel>> An array of model FQCNs that match the given logical name
     */
    final public function definitions(string $modelName): array
    {
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

    /**
     * @deprecated Will be removed in stable release
     * return compiled proxies for a given model name
     */
    final public function proxies(): ?array
    {
        if (! empty($this->_proxies)) {
            return $this->_proxies;
        }
        $models = $this->definitionsMap();
        foreach ($models as $modelName => $_) {
            $proxyClass = DomainType::Models->path(class_basename($modelName).'.php');
            $this->_proxies[$modelName] = $proxyClass;
        }

        return $this->_proxies ??= [];
    }

    final public function proxy(string $modelName): ?string
    {
        return $this->proxies()[$modelName] ?? null;
    }
}
