<?php

declare(strict_types=1);

namespace Velm\Modules;

use Illuminate\Support\Facades\DB;
use Velm\Database\Connection;
use Velm\Environment;
use Velm\Modules\Database\LaravelConnection;
use Velm\Registry;
use Velm\Schema\SchemaBuilder;
use Velm\Views\Sync\MenuSynchronizer;
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
        $specs = $this->discover($roots);
        $rows = [];

        foreach ($this->resolveOrder($specs) as $spec) {
            $installedVersion = $this->repository->installedVersion($spec->name);

            $rows[] = [
                'name' => $spec->name,
                'state' => $installedVersion === null ? 'uninstalled' : 'installed',
                'version' => $installedVersion ?? $spec->versionString(),
                'summary' => $spec->summary,
                'depends' => implode(', ', $spec->depends) ?: '—',
            ];
        }

        return $rows;
    }

    /**
     * @param  list<string>  $roots
     * @param  list<string>  $bootstrapModules
     */
    public function installBootstrap(array $roots, array $bootstrapModules): void
    {
        $this->installMany($roots, $bootstrapModules);
    }

    /**
     * @param  list<string>  $roots
     */
    public function install(string $moduleName, array $roots): void
    {
        $this->installMany($roots, [$moduleName]);
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

        $env = $this->environment($roots);
        $spec = $specs[$moduleName];
        $this->viewSynchronizer->sync($spec, $env);
        $this->menuSynchronizer->sync($spec, $env);
    }

    /**
     * @param  list<string>  $roots
     * @param  list<string>  $moduleNames
     */
    private function installMany(array $roots, array $moduleNames): void
    {
        $specs = $this->discover($roots);
        $closure = $this->closureFor($moduleNames, $specs);

        foreach ($this->resolveOrder($specs) as $spec) {
            if (! in_array($spec->name, $closure, true)) {
                continue;
            }

            if ($this->repository->isInstalled($spec->name)) {
                continue;
            }

            $this->installModule($spec, $roots);
        }
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

        if ($including !== null) {
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
        $schema = new SchemaBuilder($connection);

        Registry::with($registry, function () use ($schema, $spec, $registry): void {
            foreach ($spec->models as $modelClass) {
                if ($modelClass::isExtension()) {
                    $inherit = $modelClass::inherit();

                    if ($inherit === null || ! $registry->has($inherit)) {
                        throw new \RuntimeException(
                            "Model extension {$modelClass} targets unknown model {$inherit}.",
                        );
                    }

                    $schema->ensureTable($registry->baseModelClass($inherit));

                    continue;
                }

                $schema->ensureTable($modelClass);
            }

            $schema->ensureRelationTables($registry);
        });

        $env = new Environment($connection, $registry);
        $this->viewSynchronizer->sync($spec, $env);
        $this->menuSynchronizer->sync($spec, $env);

        $this->repository->markInstalled($spec);

        if ($spec->name === 'base') {
            $this->seedBase($roots);
        }
    }

    /**
     * @param  list<string>  $roots
     */
    private function seedBase(array $roots): void
    {
        $env = $this->environment($roots);

        if ($env->model('res.groups')->search()->count() > 0) {
            return;
        }

        $adminGroup = $env->model('res.groups')->create(['name' => 'Admin']);
        $env->model('res.groups')->create(['name' => 'User']);
        $env->model('res.groups')->create(['name' => 'Public']);

        $company = null;

        if ($env->model('res.company')->search()->count() === 0) {
            $company = $env->model('res.company')->create(['name' => 'My Company']);
        } else {
            $company = $env->model('res.company')->search(limit: 1);
        }

        $userValues = [
            'name' => 'Administrator',
            'login' => 'admin',
            'password' => 'admin',
            'group_ids' => $adminGroup->ids(),
        ];

        if ($company->count() > 0) {
            $userValues['company_id'] = $company->ids()[0];
        }

        $env->model('res.users')->create($userValues);

        $access = $env->model('ir.model.access');

        foreach ([
            'res.company',
            'res.groups',
            'res.users',
            'ir.model.access',
            'ir.rule',
            'ir.ui.view',
            'ir.ui.menu',
        ] as $model) {
            if (! $env->registry->has($model)) {
                continue;
            }

            $access->create([
                'name' => "Admin/{$model}",
                'model' => $model,
                'group_id' => $adminGroup->ids()[0],
                'perm_read' => true,
                'perm_write' => true,
                'perm_create' => true,
                'perm_unlink' => true,
            ]);
        }

        $access->create([
            'name' => 'Authenticated/res.users (self)',
            'model' => 'res.users',
            'group_id' => null,
            'perm_read' => true,
            'perm_write' => false,
            'perm_create' => false,
            'perm_unlink' => false,
        ]);

        $access->create([
            'name' => 'Authenticated/ir.ui.view (read)',
            'model' => 'ir.ui.view',
            'group_id' => null,
            'perm_read' => true,
            'perm_write' => false,
            'perm_create' => false,
            'perm_unlink' => false,
        ]);
    }

    private function velmConnection(): Connection
    {
        return $this->connection ?? new LaravelConnection(DB::connection());
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
