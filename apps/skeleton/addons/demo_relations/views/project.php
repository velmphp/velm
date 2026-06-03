<?php

declare(strict_types=1);

use Velm\Views\Authoring\Field;
use Velm\Views\Authoring\FormView;
use Velm\Views\Authoring\ListView;
use Velm\Views\Data\ViewsData;

return ViewsData::make()
    ->views(
        ListView::make('project.list')
            ->model('demo.project')
            ->title('Projects')
            ->formView('project.form')
            ->columns([
                'name',
                Field::make('tag_ids')->widget('dialog'),
                Field::make('task_ids')->widget('dialog'),
            ]),
        FormView::make('project.form')
            ->model('demo.project')
            ->section('main', 'Project', [
                'name',
                Field::make('tag_ids')->widget('dialog'),
                Field::make('task_ids')->widget('dialog'),
            ]),
    );
