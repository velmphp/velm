<?php

declare(strict_types=1);

use Velm\Views\Authoring\Card;
use Velm\Views\Authoring\DetailView;
use Velm\Views\Authoring\Field;
use Velm\Views\Authoring\FormView;
use Velm\Views\Authoring\GraphView;
use Velm\Views\Authoring\KanbanView;
use Velm\Views\Authoring\ListRowAction;
use Velm\Views\Authoring\ListView;
use Velm\Views\Authoring\Menus;
use Velm\Views\Authoring\PivotView;
use Velm\Views\Data\ViewsData;

$m = new Menus('partners');

return ViewsData::make()
    ->views(
        ListView::make('partner.list')
            ->model('res.partner')
            ->title('Partners')
            ->formView('partner.form')
            ->detailView('partner.detail')
            ->rowActions([
                ListRowAction::open(),
                ListRowAction::edit(),
            ])
            ->columns([
                'name',
                Field::make('is_company')->toggle(),
                Field::make('company_id'),
                'country_id',
                Field::make('active')->toggle(),
            ]),
        DetailView::make('partner.detail')
            ->model('res.partner')
            ->title('Partner')
            ->section('identity', 'Identity', [
                'name',
                Field::make('is_company')->toggle(),
                Field::make('active')->toggle(),
            ])
            ->section('organization', 'Organization', ['company_id'])
            ->section('address', 'Address', ['country_id']),
        FormView::make('partner.form')
            ->model('res.partner')
            ->section('identity', 'Identity', [
                'name',
                Field::make('is_company')->toggle(),
                Field::make('active')->toggle(),
            ])
            ->section('organization', 'Organization', ['company_id'])
            ->section('address', 'Address', ['country_id']),
        KanbanView::make('partner.kanban')
            ->model('res.partner')
            ->title('Partners')
            ->card(
                Card::make()
                    ->title('name')
                    ->subtitle('country_id')
                    ->fields(['is_company'])
                    ->badges([Field::make('active')->toggle()])
            )
            ->formView('partner.form')
            ->listView('partner.list'),
        GraphView::make('partner.graph')
            ->model('res.partner')
            ->title('Partners by country')
            ->groupBy('country_id')
            ->measure('__count')
            ->chart('bar')
            ->listView('partner.list'),
        PivotView::make('partner.pivot')
            ->model('res.partner')
            ->title('Partner matrix')
            ->rows(['is_company'])
            ->cols(['active'])
            ->measures(['__count'])
            ->listView('partner.list'),
    )
    ->menus(
        $m->group('contacts', 'Contacts')
            ->icon('user-group')
            ->sequence(10)
            ->children(
                $m->item('partners', 'Partners')
                    ->view('partner.list')
                    ->icon('user-group')
                    ->sequence(10),
                $m->item('partners_kanban', 'Partners kanban')
                    ->view('partner.kanban')
                    ->icon('view-columns')
                    ->sequence(20),
                $m->item('partners_graph', 'Partners graph')
                    ->view('partner.graph')
                    ->icon('chart-bar')
                    ->sequence(30),
                $m->item('partners_pivot', 'Partners pivot')
                    ->view('partner.pivot')
                    ->icon('table-cells')
                    ->sequence(40),
            ),
    );
