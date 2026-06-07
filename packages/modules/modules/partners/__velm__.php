<?php

declare(strict_types=1);

use Velm\Modules\Manifest;
use Velm\Modules\Partners\Models\CountryExtension;
use Velm\Modules\Partners\Models\Partner;
use Velm\Modules\Partners\Seeders\PartnerDemoSeeder;

return Manifest::make('partners')
    ->version(0)
    ->depends('base')
    ->models(Partner::class, CountryExtension::class)
    ->seeders(PartnerDemoSeeder::class)
    ->data('views/partner.php')
    ->summary('Companies, contacts, and the partner directory.')
    ->category('Sales');
