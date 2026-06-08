<?php

declare(strict_types=1);

use Velm\Views\Authoring\Menus;
use Velm\Views\Data\ViewsData;

$m = new Menus('workflow');

return ViewsData::make()
    ->menus(
        $m->group('workflows', 'Workflows')
            ->icon('arrow-path')
            ->sequence(45)
            ->children(
                $m->item('inbox', 'My approvals')
                    ->href('/web/workflow/inbox')
                    ->icon('inbox')
                    ->sequence(5),
                $m->item('design', 'Design workflow')
                    ->href('/web/workflow/build')
                    ->icon('pencil-square')
                    ->sequence(8),
                $m->item('definitions', 'Definitions')
                    ->view('workflow_definition.list')
                    ->icon('document-text')
                    ->sequence(15),
                $m->item('instances', 'Instances')
                    ->view('workflow_instance.list')
                    ->icon('queue-list')
                    ->sequence(20),
                $m->item('approvals', 'Approvals')
                    ->view('workflow_approval.list')
                    ->icon('check-badge')
                    ->sequence(25),
                $m->item('tasks', 'Tasks')
                    ->view('workflow_task.list')
                    ->icon('clipboard-document-check')
                    ->sequence(30),
            ),
    );
