<?php

namespace Velm\Core\Dependencies\Install;

use Velm\Core\Velm;

class ModuleInstaller
{
    public function __construct(private Velm $velm) {}

    /**
     * @throws \Exception
     */
    public function install(string $package, ?string $tenant = null): void
    {
        // Ensure it is loaded:
        $module = $this->velm->registry()->modules()->findOrFail($package);
        $resolved = $this->velm->resolver()->resolveFor($package);
        // 1. Persistence
        foreach ($resolved as $pkg) {
            $state = $this->velm->registry()->modules()->repository()->get($pkg, $tenant);
            if (empty($state)) {
                // Not installed yet
                $this->velm->registry()->modules()->repository()->install($pkg, $tenant);
            }
        }
    }
}
