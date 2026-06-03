<?php

declare(strict_types=1);

use Velm\Views\Authoring\Menus;
use Velm\Views\Data\ViewsData;

$m = new Menus('demo_relations');

return ViewsData::make()
    ->menus(
        $m->group('demos', 'Demo Module')
            ->icon('beaker')
            ->sequence(90)
            ->children(
                $m->item('demos.projects', 'Projects')
                    ->view('project.list')
                    ->icon('folder')
                    ->sequence(10),
                $m->item('demos.tasks', 'Tasks')
                    ->view('task.list')
                    ->icon('clipboard-document-list')
                    ->sequence(20),
                $m->item('demos.tags', 'Tags')
                    ->view('tag.list')
                    ->icon('tag')
                    ->sequence(30),
            ),
    );
