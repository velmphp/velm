<?php

namespace Velm\Core\Dependencies\Install;

use Illuminate\Contracts\Container\BindingResolutionException;
use Velm\Core\Velm;

final readonly class ModuleUninstaller
{
    public function __construct(private Velm $velm) {}

    /**
     * @return list<string> Packages uninstalled
     *
     * @throws BindingResolutionException
     */
    public function uninstall(string $package, ?string $tenant = null, bool $cascade = false, bool $dryRun = false): array
    {
        $packages = $this->velm->uninstallResolver()->resolve($package, $cascade);

        // 1. Runtime unload
        velm_utils()->consoleLog('Unloading packages: '.implode(', ', $packages));
        foreach ($packages as $pkg) {
            if ($dryRun) {
                velm_utils()->consoleLog('Dry run, skipping unload of package: '.$pkg);

                continue;
            }
            $this->velm->registry()->modules()->unload($pkg);
        }

        velm_utils()->consoleLog('Uninstalling modules: '.implode(', ', $packages));
        // 2. Persistence
        foreach ($packages as $pkg) {
            // If the module is installed, disable and uninstall it
            if (! $this->velm->registry()->modules()->isInstalled($pkg, $tenant)) {
                velm_utils()->consoleLog('Package not installed, skipping: '.$pkg);

                continue;
            }
            velm_utils()->consoleLog('Unloading package: '.$pkg);
            if (! $dryRun) {
                $this->velm->registry()->modules()->repository()->disable($pkg, $tenant);
            }
            velm_utils()->consoleLog('Uninstalling package: '.$pkg);
            if (! $dryRun) {
                $this->velm->registry()->modules()->repository()->uninstall($pkg, $tenant);
            }
        }

        // 3. Composer removal is intentionally NOT here
        return $packages;
    }
}
