<?php

namespace Velm\Core\Registry;

use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use JsonException;
use RuntimeException;
use Velm\Core\Modules\ModuleDescriptor;
use Velm\Core\Modules\VelmModule;
use Velm\Core\Persistence\Contracts\ModuleStateRepository;
use Velm\Core\Persistence\ModuleState;
use Velm\Core\Support\Constants;
use Velm\Core\Support\Helpers\ConsoleLogType;
use Velm\Core\Support\Repositories\ComposerRepo;

use function Laravel\Prompts\warning;

class ModuleRegistry
{
    private bool $frozen = false;

    /**
     * This is a search map
     *
     * @var array<string, ModuleDescriptor>
     */
    private array $modules = [];

    /**
     * Find locally defined modules that are not yet installed
     *
     * @var array <string, class-string>
     */
    private array $available = [];

    /**
     * @var array<ModuleDescriptor>|null
     */
    private ?array $_resolved = null;

    private ?ModuleStateRepository $_repo = null;

    /**
     * @var array<string,ModuleState>|null
     */
    private ?array $_installed = null;

    /**
     * @throws JsonException
     */
    final protected function scan(bool $rescan = false): void
    {
        if ($this->frozen && ! $rescan) {
            return;
        }
        if ($rescan) {
            $this->modules = [];
            $this->_resolved = null;
        }

        $installed = velm()->composer()->getInstalledPackages();

        foreach ($installed as $name => $json) {
            $package = velm()->composer()->getComposerJson($name);

            if (($package['type'] ?? null) !== 'velm-module') {
                continue;
            }

            if (! isset($package['extra']['velm'])) {
                continue;
            }

            $extra = $package['extra']['velm'];

            if (! is_array($extra)) {
                continue;
            }

            $class = $extra['module'] ?? null;

            if (! is_string($class)) {
                continue;
            }

            if (! class_exists($class)) {
                throw new RuntimeException(
                    "Velm module class [$class] declared in composer extra not found."
                );
            }

            if (! is_subclass_of($class, VelmModule::class)) {
                throw new RuntimeException(
                    "Velm module [$class] must extend VelmModule."
                );
            }

            $path = velm()->composer()->getPackagePath($name);

            // Get the namespace from psr-4, the one that links to app/ folder
            $namespace = null;
            if (isset($package['autoload']['psr-4']) && is_array($package['autoload']['psr-4'])) {
                foreach ($package['autoload']['psr-4'] as $ns => $nsPath) {
                    if (str_ends_with($nsPath, 'app/')) {
                        $namespace = rtrim($ns, '\\');
                        break;
                    }
                }
            }
            if ($namespace === null) {
                // Warn and skip, but don't throw
                if (app()->runningInConsole()) {
                    warning("Warning: Velm module [{$package['name']}] does not have a valid PSR-4 namespace pointing to app/ folder. Skipping registration ...\n");

                    continue;
                }
            }

            $this->modules[$package['name']] = new ModuleDescriptor(
                slug: $class::slug(),
                name: $class::name(),
                namespace: $namespace,
                path: $path,
                entryPoint: $class,
                instance: new $class,
                version: $package['version'],
                packageName: $package['name'],
                dependencies: []
            );
        }
        // Set dependencies
        foreach ($this->modules as $package => $module) {
            $this->modules[$package]->dependencies = $this->getComposerDependencies($package);
        }
    }

    /**
     * @throws JsonException
     */
    final public function getComposerJson(string $package): array
    {
        if (! isset($this->modules[$package])) {
            return [];
        }
        $module = $this->modules[$package];
        $composerPath = $module->path.'/composer.json';

        if (! is_file($composerPath)) {
            return [];
        }

        return json_decode(
            file_get_contents($composerPath),
            true,
            flags: JSON_THROW_ON_ERROR
        );
    }

    /**
     * @throws JsonException
     */
    public function getComposerDependencies(string $package): array
    {
        $repo = velm()->composer();
        $json = $repo->getComposerJson($package);
        $dependencies = [];
        if (isset($json['require']) && is_array($json['require'])) {
            foreach ($json['require'] as $dependency => $version) {
                if ($repo->isVelmModule($dependency)) {
                    $dependencies[] = $dependency;
                }
            }
        }

        return $dependencies;
    }

    /**
     * @throws JsonException
     */
    final public function registerModules(bool $force = false): void
    {
        if ($this->frozen && ! $force) {
            return;
        }
        // / STEP 1: Scan for modules
        $this->scan($force);

        // / STEP 2: Set Dependencies and register each instance
        foreach ($this->modules as $package => $module) {
            // Load dependencies from composer.json
            $this->modules[$package]->dependencies = $this->getComposerDependencies($package);
        }
    }

    final public function bootModules(): void
    {
        // Get installed modules for the current tenant
        $installedModules = $this->installed($this->tenant());
        // Get only modules which have an installed state
        foreach ($this->modules as $package => $module) {
            $state = $installedModules[$package] ?? null;
            if (filled($state)) {
                $this->modules[$package]->states[] = $state;
            }
        }
        // Remove orphans from the database (Installed modules that are not present in the registry)
        $repo = $this->repository();
        foreach ($installedModules as $package => $state) {
            if (! isset($this->modules[$package])) {
                $repo->uninstall($package, $this->tenant());
            }
        }
        // Resolve
        $resolved = $this->resolved();

        foreach ($resolved as $module) {
            $moduleInstance = $module->instance;
            if (! filled($moduleInstance)) {
                continue;
            }
            if (! $module->state($this->tenant())?->isEnabled) {
                continue;
            }

            // Register
            $moduleInstance->register();
            // Boot
            $moduleInstance->boot();
        }
    }

    final public function freeze(): void
    {
        if ($this->frozen) {
            return;
        }
        $this->frozen = true;
    }

    final public function isActive(string $package, string|int|null $tenant = null): bool
    {
        $module = $this->find($package);
        if (! filled($module)) {
            return false;
        }
        $state = $module->state($tenant);
        if (! filled($state)) {
            return false;
        }

        return $state->isEnabled;
    }

    final public function tenant(): string|int|null
    {
        // Placeholder for getting the current tenant context
        return null;
    }

    final public function repository(): ModuleStateRepository
    {
        return $this->_repo ??= app()->make(ModuleStateRepository::class);
    }

    final public function find(string $packageOrSlug, bool $orFail = false, bool $bySlug = false): ?ModuleDescriptor
    {
        /**
         * @var ModuleDescriptor|null $module
         */
        if ($bySlug) {
            $module = collect($this->modules)->where('slug', $packageOrSlug)->first();
        } else {
            $module = $this->modules[$packageOrSlug] ?? null;
        }

        return $module ?: ($orFail ? throw new RuntimeException("Module [$packageOrSlug] not found.") : null);
    }

    final public function findOrFail(string $packageOrSlug, bool $bySlug = false): ModuleDescriptor
    {
        return $this->find($packageOrSlug, orFail: true, bySlug: $bySlug);
    }

    /**
     * Return all discovered modules, indexed by package name. Unordered, unresolved.
     *
     * @return array<string, ModuleDescriptor>
     */
    final public function all(): array
    {
        return $this->modules;
    }

    /**
     * Return Resolved modules in order, based on dependencies. The result is cached.
     *
     * @return array<ModuleDescriptor>
     */
    final public function resolved(bool $force = false): array
    {
        if ($force) {
            $this->_resolved = null;
        }
        $resolved = $this->_resolved ??= \Velm::resolver()->resolve();

        // Map to descriptors
        return collect($resolved)->map(fn (string $package) => $this->modules[$package])->all();
    }

    /**
     * Return Resolved modules for a specific module, based on dependencies. The result is never cached.
     *
     * @return array<ModuleDescriptor>
     *
     * @throws Exception
     */
    final public function resolvedFor(string $package, bool $ensureActive = false, bool $installMissing = true): array
    {
        $packageNames = velm()->resolver()->resolveFor($package);
        $newInstalls = [];
        if ($installMissing) {
            // Ensure all dependencies are installed
            $repo = app()->make(ModuleStateRepository::class);
            foreach ($packageNames as $packageName) {
                // If not installed, install
                $state = $repo->get($packageName, $this->tenant());
                if (filled($state)) {
                    velm_utils()->consoleLog("Setting dependencies for module [$packageName]. Already installed.");

                    continue;
                }
                velm_utils()->consoleLog("Setting dependencies for module [$packageName]. Installing ...");
                $this->install($packageName, $this->tenant());
                $newInstalls[] = $packageName;
            }
        }
        if (filled($newInstalls)) {
            // Rescan to load newly installed modules
            if (app()->runningInConsole()) {
                velm_utils()->consoleLog('New modules installed: '.implode(', ', $newInstalls).'. Please reload the application to load them properly.');
            }
        }

        // Ensure all of them are activated and throw if some of them are not.
        if ($ensureActive) {
            $inactive = [];
            $missing = [];
            foreach ($packageNames as $packageName) {
                $module = $this->modules[$packageName];
                $state = $module->state($this->tenant());
                if (! filled($state)) {
                    $missing[] = $module->slug;
                } elseif (! $state->isEnabled) {
                    $inactive[] = $module->slug;
                }
            }
            if (filled($missing) && ! $installMissing) {
                //                throw new RuntimeException(
                //                    "Cannot resolve module [{$package}] because the following dependencies are not installed: "
                //                    .implode(', ', $missing)
                //                );
                velm_utils()->consoleLog("Cannot resolve module [{$package}] because the following dependencies are not installed: "
                    .implode(', ', $missing), ConsoleLogType::WARNING);
            }
            if (filled($inactive)) {
                throw new RuntimeException(
                    "Cannot resolve module [{$package}] because the following dependencies are inactive: "
                    .implode(', ', $inactive)
                );
            }
        }

        return collect($packageNames)->map(fn (string $package) => $this->modules[$package])->all();
    }

    /**
     * @throws BindingResolutionException
     * @throws JsonException
     */
    final public function install(string $package, ?string $tenant = null, $installMissing = false): ?ModuleState
    {
        // First, composer require the package if not present
        $plan = velm()->registry()->modules()->resolvedFor($package, true, $installMissing);
        foreach ($plan as $module) {
            if (! filled($module)) {
                // composer install with no interaction
                $composer = app()->make(ComposerRepo::class);
                $composer->require($package);
                $this->scan(true);
                $module = $this->findOrFail($package);
            }

            $repo = app()->make(ModuleStateRepository::class);
            $state = $repo->get($package, $tenant);
            if (filled($state)) {
                velm_utils()->consoleLog('Module ['.$package.'] is already installed for tenant ['.$tenant.'].', ConsoleLogType::INFO);

                return $state;
            }
            $repo->install($module->packageName, $tenant);
        }
        $mod = $this->findOrFail($package);

        return $mod->state($tenant);
    }

    public function unload(string $package): void
    {
        // Unregister the module if loaded
        $module = $this->find($package);
        if (filled($module)) {
            $module->instance->destroy();
            // Remove it from current modules
            unset($this->modules[$package]);
        }
    }

    final public function uninstall(string $package, ?string $tenant = null): void
    {
        // 1. Unregister the module if loaded
        $module = $this->find($package);
        if (filled($module)) {
            $module->instance->destroy();
            // Remove it from current modules
            unset($this->modules[$package]);
        }
        // 2. Composer remove the package
        $composer = app()->make(ComposerRepo::class);
        $composer->remove($package);
        // 3. Remove from persistence
        $repo = app()->make(ModuleStateRepository::class);
        $repo->uninstall($package, $tenant);
    }

    /* ===================================================
        PERSISTENCE
      ================================================ */
    /**
     * @returns array<string,ModuleState>
     **/
    final public function installed(?string $tenant = null): array
    {
        $repo = app(ModuleStateRepository::class);

        return $this->_installed ??= $repo->all($tenant);
    }

    /**
     * @throws BindingResolutionException
     */
    final public function isInstalled(string $package, ?string $tenant = null, bool $recheck = false): bool
    {
        if ($recheck) {
            $repo = app()->make(ModuleStateRepository::class);
            $state = $repo->get($package, $tenant);

            return filled($state);
        }
        $installed = $this->installed($tenant);

        return isset($installed[$package]) && filled($installed[$package]);
    }

    /**
     * @throws BindingResolutionException
     */
    final public function isEnabled(string $package, ?string $tenant = null, bool $recheck = false): bool
    {
        if ($recheck) {
            $repo = app()->make(ModuleStateRepository::class);
            $state = $repo->get($package, $tenant);

            return filled($state) && $state->isEnabled;
        }
        $installed = $this->installed($tenant);

        return isset($installed[$package]) && filled($installed[$package]) && $installed[$package]->isEnabled;
    }

    final public function findForClass(string $class): ?ModuleDescriptor
    {
        $class = ltrim($class, '\\');

        // Given any class, determine the module to which it belongs based on its namespace
        return array_find($this->all(), fn (ModuleDescriptor $module) => str_starts_with($class, $module->namespace.'\\'));
    }

    /**
     * @throws JsonException
     */
    final public function scanAvailable(): array
    {
        if (filled($this->available)) {
            return $this->available;
        }
        $localModulesPath = base_path(Constants::MODULES_DIRECTORY);
        if (! is_dir($localModulesPath)) {
            return [];
        }
        $dirs = scandir($localModulesPath);
        foreach ($dirs as $dir) {
            if (in_array($dir, ['.', '..'])) {
                continue;
            }
            $modulePath = $localModulesPath.DIRECTORY_SEPARATOR.$dir;
            if (! is_dir($modulePath)) {
                continue;
            }
            // Parse its composer file to get the entry point
            $composerArray = json_decode(
                file_get_contents($modulePath.DIRECTORY_SEPARATOR.'composer.json'),
                true,
                flags: JSON_THROW_ON_ERROR
            );
            if ($composerArray === null) {
                continue;
            }
            if (($composerArray['type'] ?? null) !== 'velm-module') {
                continue;
            }
            $entryPointClass = $composerArray['extra']['velm']['module'] ?? null;
            if (! is_string($entryPointClass)) {
                continue;
            }
            $namespace = null;
            if (isset($composerArray['autoload']['psr-4']) && is_array($composerArray['autoload']['psr-4'])) {
                foreach ($composerArray['autoload']['psr-4'] as $ns => $nsPath) {
                    if (str_ends_with($nsPath, 'app/')) {
                        $namespace = rtrim($ns, '\\');
                        break;
                    }
                }
            }
            if ($namespace === null) {
                // Warn and skip, but don't throw
                if (app()->runningInConsole()) {
                    warning("Warning: Velm module [{$composerArray['name']}] does not have a valid PSR-4 namespace pointing to app/ folder. Skipping registration ...\n");

                    continue;
                }
            }
            if (! class_exists($entryPointClass)) {
                $this->available[$composerArray['name']] = [
                    'namespace' => $namespace,
                    'entryPoint' => $entryPointClass,
                    'packageName' => $composerArray['name'],
                ];
            }
        }

        return $this->available;
    }

    /**
     * @returns array{namespace: string, entryPoint: class-string, packageName: string} | null
     *
     * @throws JsonException
     */
    final public function findForClassInAvailable(string $class): ?array
    {
        $available = $this->scanAvailable();
        $class = ltrim($class, '\\');

        return array_find($available, fn (array $module) => str_starts_with($class, $module['namespace'].'\\'));
    }
}
