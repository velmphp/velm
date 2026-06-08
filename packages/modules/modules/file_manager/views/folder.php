<?php

declare(strict_types=1);

use Velm\Views\Authoring\Card;
use Velm\Views\Authoring\FormView;
use Velm\Views\Authoring\KanbanView;
use Velm\Views\Authoring\ListView;
use Velm\Views\Data\ViewsData;

return ViewsData::make()
    ->views(
        ListView::make('folder.list')
            ->model('res.attachment.folder')
            ->title('Folders')
            ->formView('folder.form')
            ->columns([
                'name',
                'parent_id',
                'sequence',
            ]),
        FormView::make('folder.form')
            ->model('res.attachment.folder')
            ->section('main', 'Folder', [
                'name',
                'parent_id',
                'sequence',
                'color',
            ]),
        KanbanView::make('folder.kanban')
            ->model('res.attachment.folder')
            ->title('Folders')
            ->card(
                Card::make()
                    ->title('name')
                    ->subtitle('sequence')
                    ->fields(['color'])
            )
            ->formView('folder.form')
            ->listView('folder.list'),
    );
