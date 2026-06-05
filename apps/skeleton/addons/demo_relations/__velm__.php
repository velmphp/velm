<?php

declare(strict_types=1);

use Addons\DemoRelations\DemoRelationsInstallHooks;
use Velm\Modules\Manifest;

return Manifest::make('demo_relations')
    ->version(0, 1, 0)
    ->depends('base')
    ->data(
        'views/project.php',
        'views/task.php',
        'views/tag.php',
        'views/menu.php',
    )
    ->installHook(DemoRelationsInstallHooks::class)
    ->summary('Demo module — Many2one, One2many, and Many2many.')
    ->category('Demos');
