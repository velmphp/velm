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
        ListView::make('workflow_definition.list')
            ->model('workflow.definition')
            ->title('Workflow definitions')
            ->formView('workflow_definition.form')
            ->detailView('workflow_definition.detail')
            ->rowActions([
                ListRowAction::open(),
                ListRowAction::edit(),
                ListRowAction::link('Design', '/web/workflow/{id}/build', 'heroicon-o-pencil-square'),
            ])
            ->columns([
                'name',
                'model',
                Field::make('active')->toggle(),
            ]),
        DetailView::make('workflow_definition.detail')
            ->model('workflow.definition')
            ->title('Workflow definition')
            ->section('main', 'Workflow', [
                'name',
                'description',
                'model',
                Field::make('active')->toggle(),
            ])
            ->section('access', 'Access', ['group_ids']),
        FormView::make('workflow_definition.form')
            ->model('workflow.definition')
            ->section('main', 'Workflow', [
                'name',
                'description',
                'model',
                Field::make('active')->toggle(),
            ])
            ->section('definition', 'Definition', [
                Field::make('definition')->code('json')->wide(),
            ])
            ->section('access', 'Access', ['group_ids']),
    );
