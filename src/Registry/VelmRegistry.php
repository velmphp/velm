<?php

namespace Velm\Core\Registry;

use Velm\Core\Pipeline\ClassPipelineRegistry;

class VelmRegistry
{
    private bool $frozen = false;

    // Register the modules registry
    private ?ModuleRegistry $_moduleRegistry = null;

    private ?ModelRegistry $_modelRegistry = null;

    private ?ClassPipelineRegistry $pipelineRegistry = null;

    private ?PolicyRegistry $policyRegistry = null;

    private ?ServiceRegistry $serviceRegistry = null;

    public function modules(): ModuleRegistry
    {
        return $this->_moduleRegistry ??= new ModuleRegistry;
    }

    public function models(): ModelRegistry
    {
        return $this->_modelRegistry ??= new ModelRegistry;
    }

    public function pipeline(): ClassPipelineRegistry
    {
        return $this->pipelineRegistry ??= new ClassPipelineRegistry;
    }

    public function policies(): PolicyRegistry
    {
        return $this->policyRegistry ??= new PolicyRegistry;
    }

    public function services(): ServiceRegistry
    {
        return $this->serviceRegistry ??= new ServiceRegistry;
    }

    final public function freeze(): void
    {
        if ($this->frozen) {
            return;
        }
        $this->modules()->freeze();
        $this->frozen = true;
    }
}
