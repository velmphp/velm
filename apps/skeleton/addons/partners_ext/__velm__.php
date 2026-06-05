<?php

declare(strict_types=1);

use Velm\Modules\Manifest;

return Manifest::make('partners_ext')
    ->version(0, 1, 0)
    ->depends('partners')
    ->data('views/partner.php')
    ->summary('Skeleton demo — extend res.partner and customize partner form layout.')
    ->category('Demos');
