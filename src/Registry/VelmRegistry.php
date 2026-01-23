<?php

namespace Velm\Core\Registry;

class VelmRegistry
{
    private bool $frozen = false;

    // Register the modules registry
    private ?ModuleRegistry $_moduleRegistry = null;

    public function modules(): ModuleRegistry
    {
        return $this->_moduleRegistry ??= new ModuleRegistry;
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
