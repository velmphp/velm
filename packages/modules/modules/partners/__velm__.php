<?php

declare(strict_types=1);

use Velm\Modules\Partners\Models\Country;
use Velm\Modules\Partners\Models\Partner;

return [
    'NAME' => 'partners',
    'VERSION' => [0, 1, 0],
    'DEPENDS' => ['base'],
    'MODELS' => [
        Country::class,
        Partner::class,
    ],
    'DATA' => [],
    'SUMMARY' => 'Contacts and addresses — partners, countries.',
    'CATEGORY' => 'Sales',
];
