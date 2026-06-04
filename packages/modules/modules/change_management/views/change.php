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
        ListView::make('change.list')
            ->model('it.change')
            ->title('Change requests')
            ->formView('change.form')
            ->detailView('change.detail')
            ->rowActions([
                ListRowAction::open(),
                ListRowAction::edit(),
            ])
            ->columns([
                'reference',
                'name',
                'change_type',
                'priority',
                'risk_level',
                'requester_id',
                'planned_start',
            ]),
        DetailView::make('change.detail')
            ->model('it.change')
            ->title('Change request')
            ->section('summary', 'Summary', [
                'reference',
                'name',
                'change_type',
                'priority',
                'risk_level',
                'company_id',
            ])
            ->section('description', 'Description', [
                Field::make('description')->richText()->wide(),
                Field::make('business_justification')->richText()->wide(),
            ])
            ->section('people', 'People', [
                'requester_id',
                'implementer_id',
            ])
            ->section('schedule', 'Schedule', [
                'planned_start',
                'planned_end',
            ])
            ->section('implementation', 'Implementation', [
                'implementation_notes',
            ]),
        FormView::make('change.form')
            ->model('it.change')
            ->section('summary', 'Summary', [
                'reference',
                'name',
                'change_type',
                'priority',
                'risk_level',
                'company_id',
            ])
            ->section('description', 'Description', [
                Field::make('description')->richText()->wide(),
                Field::make('business_justification')->richText()->wide(),
            ])
            ->section('people', 'People', [
                'requester_id',
                'implementer_id',
            ])
            ->section('schedule', 'Schedule', [
                'planned_start',
                'planned_end',
            ])
            ->section('implementation', 'Implementation', [
                'implementation_notes',
            ]),
    );
