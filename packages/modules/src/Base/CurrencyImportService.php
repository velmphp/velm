<?php

declare(strict_types=1);

namespace Velm\Modules\Base;

use Velm\Environment;
use Velm\Modules\Base\Seeders\CurrencyReferenceSeeder;

/**
 * Fetches ISO-4217 currencies from the public API and upserts them on demand.
 */
final class CurrencyImportService
{
    public function __construct(
        private readonly CurrencyApiImporter $importer = new CurrencyApiImporter,
    ) {}

    /**
     * @return array{imported: int}
     */
    public function import(Environment $env): array
    {
        if (! $env->registry->has('res.currency')) {
            throw new \RuntimeException('Currency model is not installed.');
        }

        $imported = CurrencyReferenceSeeder::importProfiles($env, $this->importer->fetchProfiles());
        CurrencyReferenceSeeder::refreshExchangeRates($env);

        return ['imported' => $imported];
    }
}
