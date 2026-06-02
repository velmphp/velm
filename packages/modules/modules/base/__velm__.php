<?php

declare(strict_types=1);

use Velm\Modules\Base\Models\Company;
use Velm\Modules\Manifest;

/**
 * Bundled base module manifest.
 * @see PLAN.md — Custom module system
 */
return Manifest::make('base')
    ->version(0, 1, 0)
    ->models(Company::class)
    ->summary('Framework primitives — users, groups, views, menus, modules.')
    ->category('Administration');
