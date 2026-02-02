<?php

namespace Velm\Core\Persistence\Contracts;

use Velm\Core\Persistence\ModuleState;

interface ModuleStateRepository
{
    /** @return array<string, ModuleState> */
    public function all(?string $tenant = null): array;

    public function get(string $package, ?string $tenant = null): ?ModuleState;

    public function install(string $package, ?string $tenant = null): ?ModuleState;

    public function enable(string $package, ?string $tenant = null): void;

    public function disable(string $package, ?string $tenant = null): void;

    public function upgrade(string $package, ?string $tenant = null): void;

    public function uninstall(string $package, ?string $tenant = null): void;
}
