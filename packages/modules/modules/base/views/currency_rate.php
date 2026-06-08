<?php

declare(strict_types=1);

use Velm\Views\Authoring\Card;
use Velm\Views\Authoring\DetailView;
use Velm\Views\Authoring\FormView;
use Velm\Views\Authoring\KanbanView;
use Velm\Views\Authoring\ListRowAction;
use Velm\Views\Authoring\ListView;
use Velm\Views\Data\ViewsData;

return ViewsData::make()
    ->views(
        ListView::make('currency.rate.list')
            ->model('res.currency.rate')
            ->title('Exchange rates')
            ->formView('currency.rate.form')
            ->detailView('currency.rate.detail')
            ->rowActions([
                ListRowAction::open(),
                ListRowAction::edit(),
            ])
            ->columns([
                'name',
                'currency_id',
                'company_id',
                'rate',
            ]),
        DetailView::make('currency.rate.detail')
            ->model('res.currency.rate')
            ->title('Exchange rate')
            ->section('rate', 'Rate', [
                'name',
                'currency_id',
                'company_id',
                'rate',
            ]),
        FormView::make('currency.rate.form')
            ->model('res.currency.rate')
            ->section('rate', 'Rate', [
                'name',
                'currency_id',
                'company_id',
                'rate',
            ]),
        KanbanView::make('currency.rate.kanban')
            ->model('res.currency.rate')
            ->title('Exchange rates')
            ->card(
                Card::make()
                    ->title('currency_id')
                    ->subtitle('name')
                    ->fields(['rate'])
            )
            ->formView('currency.rate.form')
            ->listView('currency.rate.list'),
    );
