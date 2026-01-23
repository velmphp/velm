<?php

namespace Velm\Core;

use Velm\Core\Registry\VelmRegistry;

/**
 * The Velm Kernel
 */
final class Velm
{
    private bool $booted = false;

    private VelmRegistry $_registry;

    final public function registry(): VelmRegistry
    {
        return $this->_registry ??= new VelmRegistry;
    }

    /**
     * @throws \JsonException
     */
    final public function register(): void
    {
        $this->registry()->modules()->registerModules();
    }

    final public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        // Boot Logic Here
        $this->registry()->modules()->bootModules();

        $this->markAsBooted();
    }

    private function markAsBooted(): void
    {
        $this->registry()->freeze();
        $this->booted = true;
    }
}
