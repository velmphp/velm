<?php

declare(strict_types=1);

use Velm\Modules\Manifest;

return Manifest::make('partners_ext_chained')
    ->version(0, 1, 0)
    ->depends('partners', 'partners_ext')
    ->models(\Velm\Modules\Tests\Support\PartnerChainedExtension::class)
    ->summary('Fixture: chained partner extension on top of partners_ext.');
