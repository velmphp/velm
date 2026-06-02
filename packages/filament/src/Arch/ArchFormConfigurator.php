<?php

declare(strict_types=1);

namespace Velm\Filament\Arch;

use Filament\Schemas\Schema;
use Velm\Environment;

final class ArchFormConfigurator
{
    public function __construct(
        private readonly ArchSchemaBuilder $schemaBuilder = new ArchSchemaBuilder,
    ) {}

    /**
     * @param  array<string, mixed>  $arch
     */
    public function configure(Schema $schema, array $arch, Environment $env): Schema
    {
        return $schema->components($this->schemaBuilder->buildFormSchema($arch, $env));
    }
}
