<?php

declare(strict_types=1);

use Velm\Modules\Base\CompanyUiChoices;
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
        ListView::make('company.list')
            ->model('res.company')
            ->title('Companies')
            ->formView('company.form')
            ->detailView('company.detail')
            ->clickToOpen()
            ->rowActions([
                ListRowAction::open(),
                ListRowAction::edit(),
            ])
            ->columns([
                'name',
                'currency_id',
                'country_id',
                'timezone',
                'app_name',
                Field::make('active')->toggle(),
            ]),
        DetailView::make('company.detail')
            ->model('res.company')
            ->title('Company')
            ->section('main', 'Company', [
                'name',
                'currency_id',
                'country_id',
                'timezone',
                Field::make('active')->toggle(),
            ])
            ->section('contact', 'Contact & address', [
                'street',
                'city',
                'zip',
                'phone',
                'email',
                'website',
                'vat',
            ])
            ->section('branding', 'Branding & white-label', [
                'app_name',
                'app_tagline',
                Field::make('logo_url')->widget('file_url'),
                Field::make('logo_url_dark')->widget('file_url')->whenEmptyUse('logo_url'),
                'header_logo_height',
                Field::make('show_header_brand_text')->toggle(),
                Field::make('favicon_url')->widget('file_url'),
                Field::make('primary_color')->widget('color'),
                Field::make('font_family')->widget('selection')->choices(CompanyUiChoices::fontFamilies()),
                'copyright_text',
                'support_email',
                'support_url',
                Field::make('show_powered_by')->toggle(),
                Field::make('menu_layout')->widget('selection')->choices(CompanyUiChoices::menuLayouts()),
            ]),
        FormView::make('company.form')
            ->model('res.company')
            ->section('main', 'Company', [
                'name',
                'currency_id',
                'country_id',
                'timezone',
                Field::make('active')->toggle(),
            ])
            ->section('contact', 'Contact & address', [
                'street',
                'city',
                'zip',
                'phone',
                'email',
                'website',
                'vat',
            ])
            ->section('branding', 'Branding & white-label', [
                'app_name',
                'app_tagline',
                Field::make('logo_url')->widget('file_url'),
                Field::make('logo_url_dark')->widget('file_url')->whenEmptyUse('logo_url'),
                'header_logo_height',
                Field::make('show_header_brand_text')->toggle(),
                Field::make('favicon_url')->widget('file_url'),
                Field::make('primary_color')->widget('color'),
                Field::make('font_family')->widget('selection')->choices(CompanyUiChoices::fontFamilies()),
                'copyright_text',
                'support_email',
                'support_url',
                Field::make('show_powered_by')->toggle(),
                Field::make('menu_layout')->widget('selection')->choices(CompanyUiChoices::menuLayouts()),
            ]),
        KanbanView::make('company.kanban')
            ->model('res.company')
            ->title('Companies')
            ->card(
                Card::make()
                    ->title('name')
                    ->subtitle('country_id')
                    ->fields(['currency_id', 'timezone'])
                    ->badges([Field::make('active')->toggle()])
            )
            ->formView('company.form')
            ->listView('company.list'),
    );
