<?php

declare(strict_types=1);

use Velm\Views\Authoring\DetailView;
use Velm\Views\Authoring\Field;
use Velm\Views\Authoring\FormView;
use Velm\Views\Authoring\ListRowAction;
use Velm\Views\Authoring\ListView;
use Velm\Views\Authoring\Menus;
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
            ),
    );
