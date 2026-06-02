<?php

declare(strict_types=1);

namespace Velm\Modules\Schema;

use Velm\Database\Connection;
use Velm\Models\Model;
use Velm\Modules\ModuleSpec;
use Velm\Registry;
use Velm\Schema\SchemaDiff;
use Velm\Schema\SchemaDiffer;

final class ModuleSchema
{
    public function __construct(
        private readonly Connection $connection,
    ) {}

    public function diff(ModuleSpec $spec, Registry $registry): SchemaDiff
    {
        return (new SchemaDiffer($this->connection))->compute($registry, $this->ownedModelClasses($spec, $registry));
    }

    public function apply(ModuleSpec $spec, Registry $registry): SchemaDiff
    {
        $differ = new SchemaDiffer($this->connection);

        return $differ->apply($registry, $this->ownedModelClasses($spec, $registry));
    }

    /**
     * @return list<class-string<Model>>
     */
    private function ownedModelClasses(ModuleSpec $spec, Registry $registry): array
    {
        /** @var array<class-string<Model>, class-string<Model>> $classes */
        $classes = [];

        foreach ($spec->models as $modelClass) {
            if ($modelClass::isExtension()) {
                $inherit = $modelClass::inherit();

                if ($inherit === null || ! $registry->has($inherit)) {
                    continue;
                }

                $classes[$registry->baseModelClass($inherit)] = $registry->baseModelClass($inherit);

                continue;
            }

            $classes[$modelClass] = $modelClass;
        }

        return array_values($classes);
    }
}
