<?php

namespace Velm\Core\Registry;

use Velm\Core\Dependencies\Graph;
use Velm\Core\Dependencies\Resolver;
use Velm\Core\Modules\ModuleDescriptor;
use Velm\Core\Modules\VelmModule;
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

    private ?Graph $_graph = null;

    private ?Resolver $_resolver = null;

    /**
     * @var array<ModuleDescriptor>|null
     */
    private ?array $_resolved = null;

    /**
     * @throws \JsonException
     */
    final protected function scan(): void
    {
        if (filled($this->modules)) {
            return;
        }
        $installed = json_decode(
            file_get_contents(base_path('vendor/composer/installed.json')),
            true,
            flags: JSON_THROW_ON_ERROR
        );

        foreach ($installed['packages'] ?? [] as $package) {
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
                throw new \RuntimeException(
                    "Velm module class [$class] declared in composer extra not found."
                );
            }

            if (! is_subclass_of($class, VelmModule::class)) {
                throw new \RuntimeException(
                    "Velm module [$class] must extend VelmModule."
                );
            }

            $relativePath = $package['install-path'];
            // Get the absolute path using the relative path. E.g ../velm/module-name should resolve to /path/to/project/vendor/velm/module-name
            // The relative path is relative to vendor/composer/installed.json
            $path = realpath(base_path('vendor/composer/'.$relativePath));

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
                dependencies: $this->getComposerDependencies($package['name'])
            );
        }
    }

    /**
     * @throws \JsonException
     */
    final public function getComposerJson(string $packageName): array
    {
        if (! isset($this->modules[$packageName])) {
            return [];
        }
        $module = $this->modules[$packageName];
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
     * @throws \JsonException
     */
    public function getComposerDependencies(string $packageName): array
    {
        $repo = app(ComposerRepo::class);
        $json = $repo->getComposerJson($packageName);
        $dependencies = [];
        if (isset($json['dependencies']) && is_array($json['dependencies'])) {
            foreach ($json['dependencies'] as $dependency => $version) {
                $depJson = $repo->getVelmModule($dependency);
                if (filled($depJson)) {
                    $dependencies[] = $dependency;
                }
            }
        }

        return $dependencies;
    }

    public function graph(): Graph
    {
        return $this->_graph ??= tap(new Graph, function (Graph $graph) {
            foreach ($this->modules as $package => $module) {
                $graph->addNode($module->packageName);
                foreach ($module->dependencies as $dependency) {
                    $graph->addDependency($module->packageName, $dependency);
                }
            }
        });
    }

    protected function resolver(): Resolver
    {
        return $this->_resolver ??= new Resolver($this->graph());
    }

    /**
     * @throws \JsonException
     */
    final public function registerModules(): void
    {
        if ($this->frozen) {
            return;
        }
        $this->scan();

        foreach ($this->modules as $package => $module) {
            $class = $module->entryPoint;
            $slug = $class::slug();
            $this->modules[$package]->setDependencies($this->getComposerDependencies($package));
            if (! static::isActive($slug, $this->tenant())) {
                continue;
            }
            // Load dependencies from composer.json
            $module->instance->register();
        }
    }

    final public function bootModules(): void
    {
        if ($this->frozen) {
            return;
        }
        // Resolve
        $resolved = $this->resolved();

        foreach ($resolved as $module) {
            $moduleInstance = $module->instance;
            if (! filled($moduleInstance)) {
                continue;
            }
            // Boot only active modules
            if (! static::isActive($moduleInstance::slug(), $this->tenant())) {
                continue;
            }
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

    final public static function isActive(string $slug, string|int|null $tenant = null): bool
    {
        // Placeholder for actual activation check logic per tenant
        if (in_array($slug, ['academic-library'])) {
            return false;
        }

        return true;
    }

    final public function tenant(): string|int|null
    {
        // Placeholder for getting the current tenant context
        return null;
    }

    final public function find(string $moduleSlug, $orFail = false): ?ModuleDescriptor
    {
        /**
         * @var ModuleDescriptor|null $module
         */
        $module = collect($this->modules)->first(function (ModuleDescriptor $module) use ($moduleSlug) {
            return $module->slug === $moduleSlug;
        }) ?: null;

        return $module ?: ($orFail ? throw new \RuntimeException("Module [$moduleSlug] not found.") : null);
    }

    final public function findOrFail(string $moduleSlug): ModuleDescriptor
    {
        return $this->find($moduleSlug, true);
    }

    /**
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
    final public function resolved(): array
    {
        $resolved = $this->_resolved ??= $this->resolver()->resolve();

        // Map to descriptors
        return collect($resolved)->map(fn (string $package) => $this->modules[$package])->all();
    }

    /**
     * Return Resolved modules for a specific module, based on dependencies. The result is never cached.
     *
     * @return array<ModuleDescriptor>
     *
     * @throws \Exception
     */
    final public function resolvedFor(string $moduleSlug, bool $ensureActive = false): array
    {
        $packageNames = $this->resolver()->resolveFor($this->findOrFail($moduleSlug)->packageName);
        // Ensure all of them are activated and throw if some of them are not.
        if ($ensureActive) {
            $inactive = [];
            foreach ($packageNames as $packageName) {
                $module = $this->modules[$packageName];
                if (! static::isActive($module->slug, $this->tenant())) {
                    $inactive[] = $module->slug;
                }
            }
            if (filled($inactive)) {
                throw new \RuntimeException(
                    "Cannot resolve module [{$moduleSlug}] because the following dependencies are inactive: "
                    .implode(', ', $inactive)
                );
            }
        }

        return collect($packageNames)->map(fn (string $package) => $this->modules[$package])->all();
    }

    final public function resolvePath(string $moduleSlug): ?string
    {
        $module = $this->find($moduleSlug);

        return $module?->path;
    }

    final public function resolveNamespace(string $moduleSlug): ?string
    {
        $module = $this->find($moduleSlug);

        return $module?->namespace;
    }

    final public function resolvePackageName(string $moduleSlug): ?string
    {
        $module = $this->find($moduleSlug);

        return $module?->packageName;
    }

    final public function resolveDependencies(string $moduleSlug): array
    {
        $module = $this->find($moduleSlug);

        return $module?->dependencies ?? [];
    }
}
