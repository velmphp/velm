<?php

declare(strict_types=1);

use Velm\Modules\Base\AutomationUiChoices;
use Velm\Views\Authoring\Card;
use Velm\Views\Authoring\DetailView;
use Velm\Views\Authoring\Field;
use Velm\Views\Authoring\FormView;
use Velm\Views\Authoring\KanbanView;
use Velm\Views\Authoring\ListRowAction;
use Velm\Views\Authoring\ListView;
use Velm\Views\Data\ViewsData;

return ViewsData::make()
    ->views(
        ListView::make('server.action.list')
            ->model('ir.actions.server')
            ->title('Server actions')
            ->formView('server.action.form')
            ->detailView('server.action.detail')
            ->rowActions([ListRowAction::open(), ListRowAction::edit()])
            ->columns([
                'name',
                'model',
                Field::make('action_type')->widget('selection')->choices(AutomationUiChoices::serverActionTypes()),
            ]),
        DetailView::make('server.action.detail')
            ->model('ir.actions.server')
            ->title('Server action')
            ->section('main', 'Server action', [
                'name',
                'model',
                Field::make('action_type')->widget('selection')->choices(AutomationUiChoices::serverActionTypes()),
            ])
            ->section('payload', 'Payload', [
                Field::make('vals_json')->code('json')->wide(),
            ]),
        FormView::make('server.action.form')
            ->model('ir.actions.server')
            ->section('main', 'Server action', [
                'name',
                'model',
                Field::make('action_type')->widget('selection')->choices(AutomationUiChoices::serverActionTypes()),
            ])
            ->section('payload', 'Payload', [
                Field::make('vals_json')->code('json')->wide(),
            ]),
        KanbanView::make('server.action.kanban')
            ->model('ir.actions.server')
            ->title('Server actions')
            ->card(
                Card::make()
                    ->title('name')
                    ->subtitle('model')
                    ->fields([
                        Field::make('action_type')->widget('selection')->choices(AutomationUiChoices::serverActionTypes()),
                    ])
            )
            ->formView('server.action.form')
            ->listView('server.action.list'),
        ListView::make('cron.list')
            ->model('ir.cron')
            ->title('Scheduled actions')
            ->formView('cron.form')
            ->detailView('cron.detail')
            ->rowActions([ListRowAction::open(), ListRowAction::edit()])
            ->columns([
                'name',
                'action_id',
                'interval_number',
                Field::make('interval_type')->widget('selection')->choices(AutomationUiChoices::intervalTypes()),
                Field::make('active')->toggle(),
                'nextcall',
            ]),
        DetailView::make('cron.detail')
            ->model('ir.cron')
            ->title('Scheduled action')
            ->section('main', 'Scheduled action', [
                'name',
                'action_id',
                Field::make('active')->toggle(),
            ])
            ->section('schedule', 'Schedule', [
                'interval_number',
                Field::make('interval_type')->widget('selection')->choices(AutomationUiChoices::intervalTypes()),
                'nextcall',
                'lastcall',
            ]),
        FormView::make('cron.form')
            ->model('ir.cron')
            ->section('main', 'Scheduled action', [
                'name',
                'action_id',
                Field::make('active')->toggle(),
            ])
            ->section('schedule', 'Schedule', [
                'interval_number',
                Field::make('interval_type')->widget('selection')->choices(AutomationUiChoices::intervalTypes()),
                'nextcall',
            ]),
        KanbanView::make('cron.kanban')
            ->model('ir.cron')
            ->title('Scheduled actions')
            ->card(
                Card::make()
                    ->title('name')
                    ->subtitle('action_id')
                    ->fields([
                        'interval_number',
                        Field::make('interval_type')->widget('selection')->choices(AutomationUiChoices::intervalTypes()),
                        Field::make('active')->toggle(),
                    ])
            )
            ->formView('cron.form')
            ->listView('cron.list'),
    );
