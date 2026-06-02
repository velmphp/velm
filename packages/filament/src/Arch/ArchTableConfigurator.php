<?php

declare(strict_types=1);

namespace Velm\Filament\Arch;

use Closure;
use Filament\Support\ArrayRecord;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Velm\Environment;
use Velm\Views\Arch\ArchNormalizer;

final class ArchTableConfigurator
{
    public function __construct(
        private readonly ArchSchemaBuilder $schemaBuilder = new ArchSchemaBuilder,
        private readonly ListDomainBuilder $domainBuilder = new ListDomainBuilder,
    ) {}

    /**
     * @param  array<string, mixed>  $arch
     * @param  Closure(): ListQuery  $query
     */
    public function configure(Table $table, array $arch, Environment $env, Closure $query): Table
    {
        $arch = ArchNormalizer::normalizeList($arch);

        return $table
            ->columns($this->schemaBuilder->buildTableColumns($arch, $env))
            ->records(fn (): Collection => $this->fetchRecords($arch, $env, $query()))
            ->recordTitleAttribute('display_name');
    }

    /**
     * @param  array<string, mixed>  $arch
     */
    public function fetchRecords(array $arch, Environment $env, ListQuery $query = new ListQuery): Collection
    {
        $arch = ArchNormalizer::normalizeList($arch);
        $model = (string) $arch['model'];
        $domain = $this->domainBuilder->build($arch, $env, $query);
        $rows = $env->model($model)->search($domain)->read();

        return collect($rows)->map(static function (array $row): array {
            $row[ArrayRecord::getKeyName()] = $row['id'];

            return $row;
        });
    }
}
