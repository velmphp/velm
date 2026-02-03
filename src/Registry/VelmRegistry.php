<?php

namespace Velm\Core\Registry;

class VelmRegistry
{
    private bool $frozen = false;

    // Register the modules registry
    private ?ModuleRegistry $_moduleRegistry = null;

    private ?ModelRegistry $_modelRegistry = null;

    private ?CompilerRegistry $_compilerRegistry = null;

    private ?RuntimeRegistry $runtimeRegistry = null;

    public function modules(): ModuleRegistry
    {
        return $this->_moduleRegistry ??= new ModuleRegistry;
    }

    public function models(): ModelRegistry
    {
        return $this->_modelRegistry ??= new ModelRegistry;
    }

    public function compiler(): CompilerRegistry
    {
        return $this->_compilerRegistry ??= new CompilerRegistry;
    }

    public function runtime(): RuntimeRegistry
    {
        return $this->runtimeRegistry ??= new RuntimeRegistry;
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
