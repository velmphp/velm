<?php

declare(strict_types=1);

namespace Velm\Modules\Schema;

use Velm\Database\Connection;
use Velm\Fields\Many2manyField;
use Velm\Models\Model;
use Velm\Modules\ModuleDiscovery;
use Velm\Modules\ModuleModelLoader;
use Velm\Modules\ModuleSpec;
use Velm\Registry;

/**
 * Drop Velm-owned tables (models + many2many relations) while keeping
 * Laravel core tables like `users` so we can re-bootstrap the system.
 */
final class VelmSchemaReset
{
    /**
     * @param  list<string>  $roots
     */
    public function __construct(
        private readonly Connection $connection,
        private readonly ModuleDiscovery $discovery = new ModuleDiscovery,
        private readonly array $roots = [],
        private readonly array $protectedTables = [
            'users',
            'password_reset_tokens',
            'password_resets',
            'personal_access_tokens',
            'sessions',
            'jobs',
            'job_batches',
            'failed_jobs',
            'cache',
            'cache_locks',
            'migrations',
            'sqlite_sequence',
        ],
    ) {}

    /**
     * @param  list<string>  $roots
     */
    public static function make(Connection $connection, array $roots): self
    {
        return new self($connection, new ModuleDiscovery, $roots);
    }

    public function reset(): void
    {
        $registry = new Registry;
        $specs = $this->discovery->discover($this->roots);

        /** @var array<class-string<Model>, true> $models */
        $models = [];

        foreach ($specs as $spec) {
            foreach ($spec->models as $modelClass) {
                ModuleModelLoader::ensureModelClassLoaded($modelClass, $spec->path);

                if (! class_exists($modelClass)) {
                    continue;
                }

                if (! is_subclass_of($modelClass, Model::class)) {
                    continue;
                }

                $models[$modelClass] = true;

                ModuleModelLoader::registerModelClass($modelClass, $registry);
            }
        }

        $this->dropRelationTables($registry);
        $this->dropModelTables(array_keys($models), $registry);

        // Drop ir_module last so ModuleRepository::ensureTable() can recreate it.
        $this->dropTableIfExists('ir_module');
    }

    /**
     * @param  list<class-string<Model>>  $modelClasses
     */
    private function dropModelTables(array $modelClasses, Registry $registry): void
    {
        $tables = [];

        foreach ($modelClasses as $modelClass) {
            if ($modelClass::isExtension()) {
                $inherit = $modelClass::inherit();

                if ($inherit === null || ! $registry->has($inherit)) {
                    continue;
                }

                $modelClass = $registry->baseModelClass($inherit);
            }

            $table = $modelClass::table();

            if ($table === '' || in_array($table, $this->protectedTables, true)) {
                continue;
            }

            $tables[$table] = true;
        }

        $tables = array_keys($tables);
        $tables = array_reverse($tables);

        foreach ($tables as $table) {
            $this->dropTableIfExists($table);
        }
    }

    private function dropRelationTables(Registry $registry): void
    {
        /** @var array<string, true> $relationTables */
        $relationTables = [];

        foreach ($registry->models() as $modelClass) {
            foreach ($modelClass::fields() as $field) {
                if (! $field instanceof Many2manyField) {
                    continue;
                }

                [$relation] = $field->resolveSpec($modelClass, $registry);

                if ($relation !== '' && ! in_array($relation, $this->protectedTables, true)) {
                    $relationTables[$relation] = true;
                }
            }
        }

        foreach (array_keys($relationTables) as $relation) {
            $this->dropTableIfExists($relation);
        }
    }

    private function dropTableIfExists(string $table): void
    {
        $this->connection->execute('DROP TABLE IF EXISTS "'.$table.'"');
    }
}

