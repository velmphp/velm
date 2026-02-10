<?php

namespace Velm\Core\Modules;

use Velm\Core\Persistence\ModuleState;

final class ModuleDescriptor
{
    public function __construct(
        public readonly string $slug,
        public readonly string $name,
        public readonly string $namespace,
        public readonly string $path,
        /**
         * @var class-string<VelmModule> $entryPoint
         */
        public readonly string $entryPoint,
        public VelmModule $instance,
        public readonly string $version,
        public readonly string $packageName,
        public array $dependencies,
        public array $states = [],
    ) {}

    public function state(?string $tenant = null): ?ModuleState
    {
        return array_find($this->states, fn ($state) => $state->tenant === $tenant);
    }
}
