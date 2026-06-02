<?php

declare(strict_types=1);

use Velm\Modules\Manifest;

return Manifest::make('admin')
    ->version(0, 1, 0)
    ->depends('base')
    ->summary('Administration — users, groups, and system configuration.')
    ->category('Administration');
