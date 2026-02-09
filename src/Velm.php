<?php

namespace Velm\Core;

use JsonException;
use Velm\Core\Dependencies\Graph;
use Velm\Core\Dependencies\Install\ModuleInstaller;
use Velm\Core\Dependencies\Install\ModuleUninstaller;
use Velm\Core\Dependencies\Resolver;
use Velm\Core\Dependencies\ReverseGraph;
use Velm\Core\Dependencies\UninstallResolver;
use Velm\Core\Registry\VelmRegistry;
use Velm\Core\Support\Repositories\ComposerRepo;

/**
 * The Velm Kernel
 */
final class Velm
{
    private bool $booted = false;

    private VelmRegistry $_registry;

    private ModuleInstaller $_installer;

    private ModuleUninstaller $_uninstaller;

    private Resolver $_resolver;

    private UninstallResolver $_uninstallResolver;

    private Graph $_graph;

    private ReverseGraph $_reverseGraph;

    private ComposerRepo $_composer;

    final public function registry(): VelmRegistry
    {
        return $this->_registry ??= new VelmRegistry;
    }

    final public function installer(): ModuleInstaller
    {
        return $this->_installer ??= new ModuleInstaller($this);
    }

    final public function uninstaller(): ModuleUninstaller
    {
        return $this->_uninstaller ??= new ModuleUninstaller($this);
    }

    final public function graph(): Graph
    {
        return $this->_graph ??= Graph::from($this->registry()->modules()->all());
    }

    final public function resolver(): Resolver
    {
        return $this->_resolver ??= new Resolver($this->graph());
    }

    final public function reverseGraph(): ReverseGraph
    {
        return $this->_reverseGraph ??= ReverseGraph::from($this->graph());
    }

    final public function uninstallResolver(): UninstallResolver
    {
        return $this->_uninstallResolver ??= new UninstallResolver($this->reverseGraph());
    }

    final public function composer(): ComposerRepo
    {
        return $this->_composer ??= new ComposerRepo;
    }

    public function tenant(): ?string
    {
        // Get current tenant
        return null;
    }

    /**
     * @throws JsonException
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
        $this->registry()->policies()->bootPolicies();

        $this->markAsBooted();
    }

    private function markAsBooted(): void
    {
        $this->registry()->freeze();
        $this->booted = true;
    }
}
