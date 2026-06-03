<?php

declare(strict_types=1);

use Addons\DemoRelations\DemoRelationsInstallHooks;
use Addons\DemoRelations\Models\Project;
use Addons\DemoRelations\Models\Tag;
use Addons\DemoRelations\Models\Task;
use Velm\Modules\Manifest;

return Manifest::make('demo_relations')
    ->version(0, 1, 0)
    ->depends('base')
    ->models(Tag::class, Task::class, Project::class)
    ->data(
        'views/project.php',
        'views/task.php',
        'views/tag.php',
        'views/menu.php',
    )
    ->installHook(DemoRelationsInstallHooks::class)
    ->summary('Demo module — Many2one, One2many, and Many2many.')
    ->category('Demos');
