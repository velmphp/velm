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
 * Drop database tables owned exclusively by a module (dev-only uninstall cleanup).
 */
final class ModuleSchemaDrop
{
    /**
     * @param  list<string>  $roots
     * @param  list<string>  $installedModuleNames  Modules still installed after uninstall
     */
    public function __construct(
        private readonly Connection $connection,
        private readonly ModuleDiscovery $discovery = new ModuleDiscovery,
    ) {}

    /**
     * @param  list<string>  $roots
     * @param  list<string>  $installedModuleNames
     */
    public static function make(Connection $connection, array $roots, array $installedModuleNames): self
    {
        return new self($connection);
    }

    /**
     * @param  list<string>  $roots
     * @param  list<string>  $installedModuleNames
     */
    public function dropModule(ModuleSpec $spec, array $roots, array $installedModuleNames): void
    {
        $allSpecs = $this->discovery->discover($roots);
        $registry = $this->registryFor($spec, $roots);
        $modelClasses = $this->droppableModelClasses($spec, $installedModuleNames, $allSpecs);

        if ($modelClasses === []) {
            return;
        }

        $this->dropRelationTables($modelClasses, $registry);
        $this->dropModelTables($modelClasses);
    }

    /**
     * @param  list<string>  $roots
     */
    private function registryFor(ModuleSpec $spec, array $roots): Registry
    {
        $registry = new Registry;

        foreach ($this->discovery->discover($roots) as $discovered) {
            foreach ($discovered->models as $modelClass) {
                if (! class_exists($modelClass)) {
                    continue;
                }

                ModuleModelLoader::registerModelClass($modelClass, $registry);
            }
        }

        return $registry;
    }

    /**
     * @param  array<string, ModuleSpec>  $allSpecs
     * @return list<class-string<Model>>
     */
    private function droppableModelClasses(
        ModuleSpec $spec,
        array $installedModuleNames,
        array $allSpecs,
    ): array {
        /** @var array<string, true> $otherModelNames */
        $otherModelNames = [];

        foreach ($allSpecs as $name => $otherSpec) {
            if ($name === $spec->name || ! in_array($name, $installedModuleNames, true)) {
                continue;
            }

            foreach ($otherSpec->models as $modelClass) {
                if ($modelClass::isExtension()) {
                    continue;
                }

                $otherModelNames[$modelClass::name()] = true;
            }
        }

        /** @var list<class-string<Model>> $classes */
        $classes = [];

        foreach ($spec->models as $modelClass) {
            if ($modelClass::isExtension()) {
                continue;
            }

            if (isset($otherModelNames[$modelClass::name()])) {
                continue;
            }

            $classes[] = $modelClass;
        }

        return $classes;
    }

    /**
     * @param  list<class-string<Model>>  $modelClasses
     */
    private function dropModelTables(array $modelClasses): void
    {
        foreach (array_reverse($modelClasses) as $modelClass) {
            $table = $modelClass::table();

            if ($table === '') {
                continue;
            }

            $this->connection->execute('DROP TABLE IF EXISTS "'.$table.'"');
        }
    }

    /**
     * @param  list<class-string<Model>>  $modelClasses
     */
    private function dropRelationTables(array $modelClasses, Registry $registry): void
    {
        /** @var array<string, true> $tables */
        $tables = [];

        foreach ($modelClasses as $modelClass) {
            foreach ($modelClass::fields() as $field) {
                if (! $field instanceof Many2manyField) {
                    continue;
                }

                [$relation] = $field->resolveSpec($modelClass, $registry);

                if ($relation !== '') {
                    $tables[$relation] = true;
                }
            }
        }

        foreach (array_keys($tables) as $relation) {
            $this->connection->execute('DROP TABLE IF EXISTS "'.$relation.'"');
        }
    }
}
