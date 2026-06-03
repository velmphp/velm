<?php

declare(strict_types=1);

namespace Addons\DemoRelations;

use Velm\Environment;

final class DemoRelationsInstallHooks
{
    public static function install(Environment $env): void
    {
        if ($env->model('demo.project')->search()->count() > 0) {
            return;
        }

        $urgent = $env->model('demo.tag')->create(['name' => 'Urgent']);
        $feature = $env->model('demo.tag')->create(['name' => 'Feature']);
        $docs = $env->model('demo.tag')->create(['name' => 'Docs']);

        $project = $env->model('demo.project')->create([
            'name' => 'Website relaunch',
            'tag_ids' => [$urgent->ids()[0], $feature->ids()[0]],
        ]);

        $task1 = $env->model('demo.task')->create([
            'name' => 'Design mockups',
            'project_id' => $project->ids()[0],
        ]);
        $task2 = $env->model('demo.task')->create([
            'name' => 'Write relational fields guide',
            'project_id' => $project->ids()[0],
        ]);

        $env->browse('demo.project', $project->ids())->write([
            'task_ids' => [$task1->ids()[0], $task2->ids()[0]],
        ]);

        $env->model('demo.project')->create([
            'name' => 'Internal handbook',
            'tag_ids' => [$docs->ids()[0]],
        ]);
    }
}
