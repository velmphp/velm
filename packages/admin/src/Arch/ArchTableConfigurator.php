<?php

declare(strict_types=1);

namespace Velm\Admin\Arch;

use Illuminate\Support\Collection;
use Velm\Environment;
use Velm\Views\Arch\ArchNormalizer;

final class ArchTableConfigurator
{
    public function __construct(
        private readonly ListDomainBuilder $domainBuilder = new ListDomainBuilder,
    ) {}

    /**
     * @param  array<string, mixed>  $arch
     */
    public function fetchRecords(array $arch, Environment $env, ListQuery $query = new ListQuery): Collection
    {
        $arch = ArchNormalizer::normalizeList($arch);
        $model = (string) $arch['model'];
        $domain = $this->domainBuilder->build($arch, $env, $query);
        $rows = $env->model($model)->search($domain)->read();

        return collect($rows);
    }
}
