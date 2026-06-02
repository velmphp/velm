<?php

declare(strict_types=1);

use Velm\Modules\Manifest;

return Manifest::make('partners_ext')
    ->version(0, 1, 0)
    ->depends('partners')
    ->data('views/partner_ext.php')
    ->summary('Test fixture — extends partner list arch via VIEW_INHERITS.');
