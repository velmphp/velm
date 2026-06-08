<?php

declare(strict_types=1);

namespace Velm\Modules\GeoData\Seeders;

use Velm\Environment;
use Velm\Modules\GeoData\GeoReferenceSeedAction;
use Velm\Modules\Seeding\ModuleSeeder;

/**
 * Seeds only the current country (detected from the network) for fast bootstrap/tests.
 */
final class GeoReferenceSeeder implements ModuleSeeder
{
    public static function run(Environment $env): void
    {
        (new GeoReferenceSeedAction)->run($env);
    }
}
