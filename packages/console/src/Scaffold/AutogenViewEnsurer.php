<?php

declare(strict_types=1);

namespace Velm\Console\Scaffold;

use Velm\Modules\ModuleSpec;
use Velm\Models\Model;
use Velm\Registry;
use Velm\Schema\SchemaDiff;

final class AutogenViewEnsurer
{
    public function __construct(
        private readonly ViewScaffolder $viewScaffolder = new ViewScaffolder,
    ) {}

    /**
     * @return list<string> technical model names touched by the diff
     */
    public function modelsAffectedByDiff(ModuleSpec $spec, Registry $registry, SchemaDiff $diff): array
    {
        $tables = [];

        foreach ($diff->newTables as [$table]) {
            $tables[$table] = true;
        }

        foreach ($diff->newColumns as [$table]) {
            $tables[$table] = true;
        }

        if ($tables === []) {
            return [];
        }

        $models = [];

        foreach ($spec->models as $class) {
            if (! is_subclass_of($class, Model::class)) {
                continue;
            }

            $technical = $class::name();

            if (! $registry->has($technical)) {
                continue;
            }

            if (isset($tables[$class::table()])) {
                $models[] = $technical;
            }
        }

        sort($models);

        return $models;
    }

    public function modelHasListView(ModuleSpec $spec, string $technical): bool
    {
        $stem = explode('.', $technical);
        $fileStem = end($stem) ?: $technical;

        if (is_file($spec->path.'/views/'.$fileStem.'.php')) {
            return true;
        }

        foreach ($spec->data as $relative) {
            $path = $spec->path.'/'.$relative;

            if (! is_file($path)) {
                continue;
            }

            $contents = file_get_contents($path);

            if ($contents !== false && str_contains($contents, "->model('{$technical}')")) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $modelNames
     * @return list<string> paths created
     */
    public function ensureViews(
        ModuleSpec $spec,
        array $modelNames,
        Registry $registry,
        bool $force = false,
    ): array {
        $created = [];

        foreach ($modelNames as $technical) {
            if ($this->modelHasListView($spec, $technical)) {
                continue;
            }

            $result = $this->viewScaffolder->scaffold(
                $technical,
                $spec->name,
                $spec->path,
                true,
                $force,
                $registry,
            );

            $created[] = $result['path'];
        }

        return $created;
    }
}
