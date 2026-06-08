<?php

declare(strict_types=1);

use Velm\Views\Authoring\Action;
use Velm\Views\Authoring\ActionVariant;
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
        ListView::make('continent.list')
            ->model('res.continent')
            ->title('Continents')
            ->formView('continent.form')
            ->detailView('continent.detail')
            ->rowActions([ListRowAction::open(), ListRowAction::edit()])
            ->columns(['code', 'name']),
        DetailView::make('continent.detail')
            ->model('res.continent')
            ->title('Continent')
            ->section('identity', 'Identity', ['code', 'name']),
        FormView::make('continent.form')
            ->model('res.continent')
            ->section('identity', 'Identity', ['code', 'name']),
        KanbanView::make('continent.kanban')
            ->model('res.continent')
            ->title('Continents')
            ->card(Card::make()->title('name')->subtitle('code'))
            ->formView('continent.form')
            ->listView('continent.list'),
        ListView::make('country.list')
            ->model('res.country')
            ->title('Countries')
            ->formView('country.form')
            ->detailView('country.detail')
            ->pageActions([
                Action::make('Import full geography')
                    ->url('/web/geo/import')
                    ->confirm('Download all countries, states and cities from public APIs? This may take several minutes.')
                    ->variant(ActionVariant::Warning)
                    ->perm('write'),
            ])
            ->rowActions([ListRowAction::open(), ListRowAction::edit()])
            ->columns([
                'flag_emoji',
                'name',
                'code',
                'iso3',
                'continent_id',
                'capital',
                'currency_id',
                'phone_code',
            ]),
        DetailView::make('country.detail')
            ->model('res.country')
            ->title('Country')
            ->section('identity', 'Identity', [
                'flag_emoji',
                'name',
                'code',
                'iso3',
                'continent_id',
            ])
            ->section('facts', 'Facts', [
                'capital',
                'currency_id',
                'phone_code',
                'population',
            ]),
        KanbanView::make('country.kanban')
            ->model('res.country')
            ->title('Countries')
            ->card(
                Card::make()
                    ->title('name')
                    ->subtitle('code')
                    ->fields(['capital', 'currency_id'])
            )
            ->formView('country.form')
            ->listView('country.list'),
        FormView::make('country.form')
            ->model('res.country')
            ->section('identity', 'Identity', [
                'flag_emoji',
                'name',
                'code',
                'iso3',
                'continent_id',
            ])
            ->section('facts', 'Facts', [
                'capital',
                'currency_id',
                'phone_code',
                'population',
            ]),
        ListView::make('state.list')
            ->model('res.country.state')
            ->title('States / provinces')
            ->formView('state.form')
            ->detailView('state.detail')
            ->rowActions([ListRowAction::open(), ListRowAction::edit()])
            ->columns(['name', 'short_code', 'code', 'type', 'country_id']),
        DetailView::make('state.detail')
            ->model('res.country.state')
            ->title('State')
            ->section('identity', 'Identity', ['name', 'short_code', 'code', 'type', 'country_id']),
        FormView::make('state.form')
            ->model('res.country.state')
            ->section('identity', 'Identity', ['name', 'short_code', 'code', 'type', 'country_id']),
        KanbanView::make('state.kanban')
            ->model('res.country.state')
            ->title('States / provinces')
            ->card(
                Card::make()
                    ->title('name')
                    ->subtitle('type')
                    ->fields(['short_code', 'code'])
            )
            ->formView('state.form')
            ->listView('state.list'),
        ListView::make('city.list')
            ->model('res.city')
            ->title('Cities')
            ->formView('city.form')
            ->detailView('city.detail')
            ->rowActions([ListRowAction::open(), ListRowAction::edit()])
            ->columns([
                'name',
                'country_id',
                'state_id',
                'population',
                Field::make('is_capital')->toggle(),
            ]),
        DetailView::make('city.detail')
            ->model('res.city')
            ->title('City')
            ->section('identity', 'Identity', [
                'name',
                'country_id',
                'state_id',
                Field::make('is_capital')->toggle(),
                'population',
                'timezone',
            ]),
        FormView::make('city.form')
            ->model('res.city')
            ->section('identity', 'Identity', [
                'name',
                'country_id',
                'state_id',
                Field::make('is_capital')->toggle(),
                'population',
                'timezone',
            ]),
        KanbanView::make('city.kanban')
            ->model('res.city')
            ->title('Cities')
            ->card(
                Card::make()
                    ->title('name')
                    ->subtitle('state_id')
                    ->fields(['population'])
                    ->badges([Field::make('is_capital')->toggle()])
            )
            ->formView('city.form')
            ->listView('city.list'),
    );
