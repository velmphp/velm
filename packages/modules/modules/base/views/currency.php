<?php

declare(strict_types=1);

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
        ListView::make('currency.list')
            ->model('res.currency')
            ->title('Currencies')
            ->formView('currency.form')
            ->detailView('currency.detail')
            ->clickToOpen()
            ->rowActions([
                ListRowAction::open(),
                ListRowAction::edit(),
            ])
            ->columns([
                'name',
                'full_name',
                'symbol',
                'decimal_places',
                Field::make('active')->toggle(),
            ]),
        DetailView::make('currency.detail')
            ->model('res.currency')
            ->title('Currency')
            ->section('identity', 'Currency', [
                'name',
                'full_name',
                'symbol',
                'decimal_places',
                Field::make('active')->toggle(),
            ])
            ->section('rates', 'Exchange rates', ['rate_ids']),
        FormView::make('currency.form')
            ->model('res.currency')
            ->section('identity', 'Currency', [
                'name',
                'full_name',
                'symbol',
                'decimal_places',
                Field::make('active')->toggle(),
            ]),
        KanbanView::make('currency.kanban')
            ->model('res.currency')
            ->title('Currencies')
            ->card(
                Card::make()
                    ->title('name')
                    ->subtitle('full_name')
                    ->fields(['symbol', 'decimal_places'])
                    ->badges([Field::make('active')->toggle()])
            )
            ->formView('currency.form')
            ->listView('currency.list'),
    );
