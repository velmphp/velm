<?php

declare(strict_types=1);

namespace Velm\Modules;

use Illuminate\Support\Facades\DB;
use Velm\Database\Connection;
use Velm\Environment;
use Velm\Models\Model;
use Velm\Modules\Database\LaravelConnection;
use Velm\Modules\Migrations\ModuleMigrationRunner;
use Velm\Modules\Schema\ModuleSchema;
use Velm\Modules\ModuleVersion;
use Velm\Registry;
use Velm\Schema\SchemaDiff;
use Velm\Schema\SchemaDiffer;
use Velm\Views\Sync\MenuSynchronizer;
use Velm\Views\Sync\UiSyncDiffer;
use Velm\Views\Sync\ViewSynchronizer;

final class ModuleInstaller
{
    public function __construct(
        private readonly ModuleDiscovery $discovery = new ModuleDiscovery,
        private readonly DependencyResolver $resolver = new DependencyResolver,
        private readonly ModuleRepository $repository = new ModuleRepository,
        private readonly ModuleModelLoader $modelLoader = new ModuleModelLoader,
        private readonly ViewSynchronizer $viewSynchronizer = new ViewSynchronizer,
        private readonly MenuSynchronizer $menuSynchronizer = new MenuSynchronizer,
        private readonly ModuleHookRunner $hookRunner = new ModuleHookRunner,
        private readonly ?Connection $connection = null,
    ) {}

    /**
     * @param  list<string>  $roots
     * @return array<string, ModuleSpec>
     */
    public function discover(array $roots): array
    {
        return $this->discovery->discover($roots);
    }

    /**
     * @param  array<string, ModuleSpec>  $specs
     * @return list<ModuleSpec>
     */
    public function resolveOrder(array $specs): array
    {
        return $this->resolver->resolve($specs);
    }

    /**
     * @param  list<string>  $roots
     * @return list<array{name: string, state: string, version: string|null, summary: string, depends: string}>
     */
    public function catalog(array $roots): array
    {
        return (new AppsCatalog(
            $this->discovery,
            $this->resolver,
            $this->repository,
            $this,
        ))->entries($roots);
    }

    /**
     * @param  list<string>  $roots
     * @param  list<string>  $bootstrapModules
     */
    public function installBootstrap(array $roots, array $bootstrapModules): void
    {
        $this->migrateMany($roots, $bootstrapModules);
    }

    /**
     * @param  list<string>  $roots
     */
    public function install(string $moduleName, array $roots): void
    {
        $this->migrate($moduleName, $roots);
    }

    /**
     * Bring an installed module up to date: version migrations when the manifest
     * version increased, otherwise schema diff + view/menu sync (same as Sync).
     *
     * @param  list<string>  $roots
     */
    public function reconcile(string $moduleName, array $roots): void
    {
        $specs = $this->discover($roots);

        if (! isset($specs[$moduleName])) {
            throw new \InvalidArgumentException("Module {$moduleName} was not discovered.");
        }

        $spec = $specs[$moduleName];

        if (! $this->repository->isInstalled($moduleName)) {
            $this->installModule($spec, $roots);

            return;
        }

        $this->reconcileInstalled($spec, $roots);
    }

    /**
     * Install or upgrade modules (transitive closure).
     *
     * @param  list<string>  $roots
     */
    public function migrate(string $moduleName, array $roots): void
    {
        $this->migrateMany($roots, [$moduleName]);
    }

    /**
     * @param  list<string>  $roots
     */
    public function diff(string $moduleName, array $roots): SchemaDiff
    {
        $specs = $this->discover($roots);

        if (! isset($specs[$moduleName])) {
            throw new \InvalidArgumentException("Module {$moduleName} was not discovered.");
        }

        $spec = $specs[$moduleName];
        $registry = $this->registry($roots, $spec);

        return (new ModuleSchema($this->velmConnection()))->diff($spec, $registry);
    }

    public function countNullRows(string $table, string $column): int
    {
        return (new SchemaDiffer($this->velmConnection()))->countNullRows($table, $column);
    }

    /**
     * @return list<array{name: string, installed: string|null, manifest: string, status: string}>
     */
    public function schemaStatus(array $roots): array
    {
        $rows = [];

        foreach ($this->catalog($roots) as $entry) {
            if ($entry['state'] !== 'installed') {
                continue;
            }

            $specs = $this->discover($roots);
            $spec = $specs[$entry['name']] ?? null;

            if ($spec === null) {
                continue;
            }

            $installed = ModuleVersion::parse($entry['installed_version'] ?? '0.0.0');
            $status = ModuleVersion::needsUpgrade($installed, $spec->version) ? 'upgrade' : 'ok';

            $rows[] = [
                'name' => $entry['name'],
                'installed' => $entry['installed_version'],
                'manifest' => $spec->versionString(),
                'status' => $status,
            ];
        }

        return $rows;
    }

    /**
     * @param  list<string>  $roots
     * @param  list<string>  $protectedModules
     */
    public function uninstallPreview(
        string $moduleName,
        array $roots,
        array $protectedModules = ['base', 'admin'],
    ): ModuleUninstallPreview {
        $specs = $this->discover($roots);

        if (! isset($specs[$moduleName])) {
            throw new \InvalidArgumentException("Module {$moduleName} was not discovered.");
        }

        if (! $this->repository->isInstalled($moduleName)) {
            throw new \RuntimeException("Module {$moduleName} is not installed.");
        }

        $systemBlockers = [];

        if (in_array($moduleName, $protectedModules, true)) {
            $systemBlockers[] = "{$moduleName} is a protected system module";
        }

        $reverseDependencies = $this->reverseDependencies($moduleName, $specs);
        $modelExtensions = $this->modelExtensionsBlockingUninstall($moduleName, $specs);

        return new ModuleUninstallPreview(
            module: $moduleName,
            canUninstall: $systemBlockers === [] && $reverseDependencies === [] && $modelExtensions === [],
            reverseDependencies: $reverseDependencies,
            modelExtensions: $modelExtensions,
            systemBlockers: $systemBlockers,
        );
    }

    /**
     * @param  list<string>  $roots
     * @param  list<string>  $protectedModules
     */
    public function uninstall(
        string $moduleName,
        array $roots,
        array $protectedModules = ['base', 'admin'],
    ): void {
        $preview = $this->uninstallPreview($moduleName, $roots, $protectedModules);

        if (! $preview->canUninstall) {
            throw new \RuntimeException(
                "Cannot uninstall {$moduleName}: ".implode('; ', $preview->blockers()),
            );
        }

        $env = $this->environment($roots);

        $this->viewSynchronizer->purgeModule($moduleName, $env);
        $this->menuSynchronizer->purgeModule($moduleName, $env);

        $this->repository->markUninstalled($moduleName);
    }

    /**
     * @param  list<string>  $roots
     */
    public function sync(string $moduleName, array $roots): void
    {
        $specs = $this->discover($roots);

        if (! isset($specs[$moduleName])) {
            throw new \InvalidArgumentException("Module {$moduleName} was not discovered.");
        }

        if (! $this->repository->isInstalled($moduleName)) {
            throw new \RuntimeException("Module {$moduleName} is not installed. Run module:install first.");
        }

        $spec = $specs[$moduleName];
        $registry = $this->registry($roots, $spec);
        $connection = $this->velmConnection();
        $env = new Environment($connection, $registry);

        $this->hookRunner->runSyncHook($spec->syncHook, $env);
        (new ModuleSchema($connection))->apply($spec, $registry);

        $this->viewSynchronizer->sync($spec, $env);
        $this->menuSynchronizer->sync($spec, $env);
    }

    /**
     * @param  list<string>  $roots
     * @param  list<string>  $moduleNames
     */
    private function migrateMany(array $roots, array $moduleNames): void
    {
        $specs = $this->discover($roots);
        $closure = $this->closureFor($moduleNames, $specs);

        foreach ($this->resolveOrder($specs) as $spec) {
            if (! in_array($spec->name, $closure, true)) {
                continue;
            }

            if ($this->repository->isInstalled($spec->name)) {
                $this->reconcileInstalled($spec, $roots);

                continue;
            }

            $this->installModule($spec, $roots);
        }
    }

    /**
     * @param  list<string>  $roots
     */
    public function hasPendingSchemaDiff(string $moduleName, array $roots): bool
    {
        try {
            $diff = $this->diff($moduleName, $roots);

            return $diff->isSyncActionable($this->canAlterColumnNullability());
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @param  list<string>  $roots
     */
    public function uiSyncDiff(string $moduleName, array $roots): \Velm\Views\Sync\UiSyncDiff
    {
        $specs = $this->discover($roots);

        if (! isset($specs[$moduleName])) {
            throw new \InvalidArgumentException("Module {$moduleName} was not discovered.");
        }

        if (! $this->repository->isInstalled($moduleName)) {
            return new \Velm\Views\Sync\UiSyncDiff;
        }

        $spec = $specs[$moduleName];
        $env = new Environment($this->velmConnection(), $this->registry($roots, $spec));

        return (new UiSyncDiffer)->diff($spec, $env);
    }

    /**
     * @param  list<string>  $roots
     */
    public function hasPendingUiSync(string $moduleName, array $roots): bool
    {
        try {
            return $this->uiSyncDiff($moduleName, $roots)->hasChanges();
        } catch (\Throwable) {
            return false;
        }
    }

    public function canAlterColumnNullability(): bool
    {
        return (new SchemaDiffer($this->velmConnection()))->supportsAlterColumnNullability();
    }

    /**
     * @param  list<string>  $roots
     */
    private function reconcileInstalled(ModuleSpec $spec, array $roots): void
    {
        if ($this->needsUpgrade($spec)) {
            $this->upgradeModule($spec, $roots);

            return;
        }

        if ($this->hasPendingSchemaDiff($spec->name, $roots) || $this->hasPendingUiSync($spec->name, $roots)) {
            $this->sync($spec->name, $roots);
        }
    }

    private function needsUpgrade(ModuleSpec $spec): bool
    {
        $installed = $this->repository->installedVersion($spec->name);

        if ($installed === null) {
            return false;
        }

        return ModuleVersion::needsUpgrade(
            ModuleVersion::parse($installed),
            $spec->version,
        );
    }

    /**
     * @param  list<string>  $roots
     */
    public function environment(array $roots): Environment
    {
        return new Environment($this->velmConnection(), $this->registry($roots));
    }

    /**
     * @param  list<string>  $roots
     */
    private function registry(array $roots, ?ModuleSpec $including = null): Registry
    {
        $registry = new Registry;
        $this->modelLoader->loadInstalled($roots, $registry, $this->discovery, $this->resolver, $this->repository);

        if ($including !== null && ! $this->repository->isInstalled($including->name)) {
            $this->modelLoader->load($including, $registry);
        }

        return $registry;
    }

    /**
     * @param  list<string>  $roots
     */
    private function installModule(ModuleSpec $spec, array $roots): void
    {
        $registry = $this->registry($roots, $spec);
        $connection = $this->velmConnection();
        $env = new Environment($connection, $registry);

        (new ModuleMigrationRunner)->run($env, $spec, [], $spec->version);
        (new ModuleSchema($connection))->apply($spec, $registry);
        $this->hookRunner->runInstallHook($spec->installHook, $env);

        $this->viewSynchronizer->sync($spec, $env);
        $this->menuSynchronizer->sync($spec, $env);

        $this->repository->markInstalled($spec);
    }

    /**
     * @param  list<string>  $roots
     */
    private function upgradeModule(ModuleSpec $spec, array $roots): void
    {
        $installed = ModuleVersion::parse($this->repository->installedVersion($spec->name) ?? '0.0.0');
        $registry = $this->registry($roots, $spec);
        $connection = $this->velmConnection();
        $env = new Environment($connection, $registry);

        (new ModuleMigrationRunner)->run($env, $spec, $installed, $spec->version);
        $this->applySchema($spec, $connection, $registry, $env);

        $this->viewSynchronizer->sync($spec, $env);
        $this->menuSynchronizer->sync($spec, $env);

        $this->repository->markInstalled($spec);
    }

    private function applySchema(
        ModuleSpec $spec,
        Connection $connection,
        Registry $registry,
        Environment $env,
    ): void {
        $this->hookRunner->runSyncHook($spec->syncHook, $env);
        (new ModuleSchema($connection))->apply($spec, $registry);
    }

    private function velmConnection(): Connection
    {
        return $this->connection ?? new LaravelConnection(DB::connection());
    }

    /**
     * @param  array<string, ModuleSpec>  $specs
     * @return list<string>
     */
    private function reverseDependencies(string $moduleName, array $specs): array
    {
        $dependents = [];

        foreach ($specs as $spec) {
            if ($spec->name === $moduleName) {
                continue;
            }

            if (! $this->repository->isInstalled($spec->name)) {
                continue;
            }

            if (in_array($moduleName, $spec->depends, true)) {
                $dependents[] = $spec->name;
            }
        }

        sort($dependents, SORT_NATURAL | SORT_FLAG_CASE);

        return $dependents;
    }

    /**
     * @param  array<string, ModuleSpec>  $specs
     * @return list<string>
     */
    private function modelExtensionsBlockingUninstall(string $moduleName, array $specs): array
    {
        $targetSpec = $specs[$moduleName] ?? null;

        if ($targetSpec === null) {
            return [];
        }

        $targetModels = $this->baseModelNames($targetSpec);
        $blockers = [];

        foreach ($specs as $spec) {
            if ($spec->name === $moduleName) {
                continue;
            }

            if (! $this->repository->isInstalled($spec->name)) {
                continue;
            }

            foreach ($spec->models as $modelClass) {
                ModuleModelLoader::ensureModelClassLoaded($modelClass, $spec->path);

                if (! class_exists($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
                    continue;
                }

                if (! $modelClass::isExtension()) {
                    continue;
                }

                $inherit = $modelClass::inherit();

                if ($inherit !== null && in_array($inherit, $targetModels, true)) {
                    $blockers[] = $spec->name;

                    break;
                }
            }
        }

        $blockers = array_values(array_unique($blockers));
        sort($blockers, SORT_NATURAL | SORT_FLAG_CASE);

        return $blockers;
    }

    /**
     * @return list<string>
     */
    private function baseModelNames(ModuleSpec $spec): array
    {
        $names = [];

        foreach ($spec->models as $modelClass) {
            ModuleModelLoader::ensureModelClassLoaded($modelClass, $spec->path);

            if (! class_exists($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
                continue;
            }

            if ($modelClass::isExtension()) {
                continue;
            }

            $name = $modelClass::name();

            if ($name !== '') {
                $names[] = $name;
            }
        }

        return $names;
    }

    /**
     * @param  list<string>  $moduleNames
     * @param  array<string, ModuleSpec>  $specs
     * @return list<string>
     */
    private function closureFor(array $moduleNames, array $specs): array
    {
        $pending = array_values(array_unique($moduleNames));
        $closure = [];

        while ($pending !== []) {
            $name = array_shift($pending);

            if (in_array($name, $closure, true)) {
                continue;
            }

            if (! isset($specs[$name])) {
                throw new \InvalidArgumentException("Module {$name} was not discovered.");
            }

            foreach ($specs[$name]->depends as $dependency) {
                if (! in_array($dependency, $closure, true) && ! in_array($dependency, $pending, true)) {
                    $pending[] = $dependency;
                }
            }

            $closure[] = $name;
        }

        return $closure;
    }
}
