<?php

declare(strict_types=1);

use Velm\Modules\Manifest;

return Manifest::make('partners_ext_independent')
    ->version(0, 1, 0)
    ->depends('partners')
    ->models(\Velm\Modules\Tests\Support\PartnerIndependentExtension::class)
    ->summary('Fixture: independent partner extension (collision scenario).');
