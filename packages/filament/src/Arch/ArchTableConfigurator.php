<?php

declare(strict_types=1);

namespace Velm\Filament\Arch;

use Filament\Support\ArrayRecord;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Velm\Environment;
use Velm\Views\Arch\ArchNormalizer;

final class ArchTableConfigurator
{
    public function __construct(
        private readonly ArchSchemaBuilder $schemaBuilder = new ArchSchemaBuilder,
    ) {}

    /**
     * @param  array<string, mixed>  $arch
     */
    public function configure(Table $table, array $arch, Environment $env): Table
    {
        $arch = ArchNormalizer::normalizeList($arch);

        return $table
            ->columns($this->schemaBuilder->buildTableColumns($arch, $env))
            ->records(fn (): Collection => $this->fetchRecords($arch, $env))
            ->recordTitleAttribute('display_name');
    }

    /**
     * @param  array<string, mixed>  $arch
     */
    public function fetchRecords(array $arch, Environment $env): Collection
    {
        $arch = ArchNormalizer::normalizeList($arch);
        $model = (string) $arch['model'];
        $staticDomain = $arch['domain'] ?? [];
        $rows = $env->model($model)->search(is_array($staticDomain) ? $staticDomain : [])->read();

        return collect($rows)->map(static function (array $row): array {
            $row[ArrayRecord::getKeyName()] = $row['id'];

            return $row;
        });
    }
}
