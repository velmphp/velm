<?php

declare(strict_types=1);

use Velm\Views\Authoring\DetailView;
use Velm\Views\Authoring\Field;
use Velm\Views\Authoring\FormView;
use Velm\Views\Authoring\ListRowAction;
use Velm\Views\Authoring\ListView;
use Velm\Views\Data\ViewsData;

return ViewsData::make()
    ->views(
        ListView::make('task.list')
            ->model('demo.task')
            ->title('Tasks')
            ->formView('task.form')
            ->detailView('task.detail')
            ->clickToOpen()
            ->rowActions([ListRowAction::open(), ListRowAction::edit()])
            ->columns([
                'name',
                'project_id',
                Field::make('cover_id')->widget('file'),
                Field::make('category'),
                Field::make('created_at'),
                Field::make('updated_at'),
            ]),
        DetailView::make('task.detail')
            ->model('demo.task')
            ->title('Task')
            ->section('main', 'Task', [
                'name',
                'project_id',
                Field::make('cover_id')->widget('file')->accept('image/*'),
                'category',
            ])->cols(3),
        FormView::make('task.form')
            ->model('demo.task')
            ->section('main', 'Task', [
                'name',
                'project_id',
                Field::make('cover_id')->widget('file')->accept('image/*'),
                'category',
            ])->cols(3),
    );
