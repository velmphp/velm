<?php

declare(strict_types=1);

use Velm\Modules\Manifest;
use Velm\Modules\Partners\Models\Country;
use Velm\Modules\Partners\Models\Partner;

return Manifest::make('partners')
    ->version(0, 1, 0)
    ->depends('base')
    ->models(Country::class, Partner::class)
    ->summary('Contacts and addresses — partners, countries.')
    ->category('Sales');
