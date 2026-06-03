<?php

declare(strict_types=1);

use Velm\Views\Authoring\FormView;
use Velm\Views\Authoring\ListView;
use Velm\Views\Data\ViewsData;

return ViewsData::make()
    ->views(
        ListView::make('task.list')
            ->model('demo.task')
            ->title('Tasks')
            ->formView('task.form')
            ->columns([
                'name',
                'project_id',
            ]),
        FormView::make('task.form')
            ->model('demo.task')
            ->section('main', 'Task', [
                'name',
                'project_id',
            ]),
    );
