<?php

namespace Velm\Core\Modules;

final class ModuleDescriptor
{
    public function __construct(
        public readonly string $slug,
        public readonly string $name,
        public readonly string $namespace,
        public readonly string $path,
        /**
         *@var class-string<VelmModule> $entryPoint
         */
        public readonly string $entryPoint,
        public VelmModule $instance,
        public readonly string $version,
        public readonly string $packageName,
        public array  $dependencies
    ) {}

    public function setInstance(VelmModule $instance): void
    {
        $this->instance = $instance;
    }

    public function setDependencies(array $dependencies): void
    {
        $this->dependencies = $dependencies;
    }
}
