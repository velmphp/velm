<?php

declare(strict_types=1);

use Velm\Modules\Base\BaseInstallHooks;
use Velm\Modules\Base\Models\Attachment;
use Velm\Modules\Base\Models\Company;
use Velm\Modules\Base\Models\Cron;
use Velm\Modules\Base\Models\Group;
use Velm\Modules\Base\Models\ModelAccess;
use Velm\Modules\Base\Models\Rule;
use Velm\Modules\Base\Models\ServerAction;
use Velm\Modules\Base\Models\UiMenu;
use Velm\Modules\Base\Models\UiView;
use Velm\Modules\Base\Models\User;
use Velm\Modules\Manifest;

/**
 * Bundled base module manifest.
 * @see PLAN.md — Custom module system
 */
return Manifest::make('base')
    ->version(0, 2, 0)
    ->models(
        Attachment::class,
        Company::class,
        Group::class,
        User::class,
        ModelAccess::class,
        Rule::class,
        ServerAction::class,
        Cron::class,
        UiView::class,
        UiMenu::class,
    )
    ->installHook(BaseInstallHooks::class)
    ->data('views/company.php')
    ->summary('Framework primitives — users, groups, views, menus, modules.')
    ->category('Administration');
