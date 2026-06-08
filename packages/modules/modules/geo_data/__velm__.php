<?php

declare(strict_types=1);

use Velm\Modules\GeoData\Models\City;
use Velm\Modules\GeoData\Models\Continent;
use Velm\Modules\GeoData\Models\CountryExtension;
use Velm\Modules\GeoData\Models\CountryState;
use Velm\Modules\GeoData\Seeders\GeoReferenceSeeder;
use Velm\Modules\Manifest;

return Manifest::make('geo_data')
    ->version(0, 5, 0)
    ->depends('base', 'admin')
    ->models(
        Continent::class,
        CountryState::class,
        City::class,
        CountryExtension::class,
    )
    ->seeders(GeoReferenceSeeder::class)
    ->data('views/geo.php', 'views/menu.php')
    ->summary('Core geography: continents, countries, states/provinces, and cities.')
    ->category('Core');
