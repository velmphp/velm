<?php

namespace Velm\Core\Registry;

use Velm\Core\Domain\Models\VelmModel;

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
            $this->_models[$package][$model::velm()->name] = $model;
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
     * return compiled proxies for a given model name
     */
    final public function proxies(): ?array
    {
        if (empty($this->_proxies)) {
            // Boot proxies
            foreach ($this->definitionsMap() as $package => $models) {
                if (empty($models)) {
                    continue;
                }
                $this->_proxies[$package] = $models[0]::velm()->proxyCandidateClass;
            }
        }

        // If no logical name is provided, return all proxies
        return $this->_proxies;
    }

    final public function proxy(string $modelName): ?string
    {
        return $this->proxies()[$modelName] ?? null;
    }
}
