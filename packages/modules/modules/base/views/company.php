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
                'app_name',
                'timezone',
                'primary_color',
                Field::make('active')->toggle(),
            ]),
        DetailView::make('company.detail')
            ->model('res.company')
            ->title('Company')
            ->section('main', 'Company', [
                'name',
                'timezone',
                Field::make('active')->toggle(),
            ])
            ->section('branding', 'Branding', [
                'app_name',
                'app_tagline',
                'logo_url',
                'logo_url_dark',
                'primary_color',
                'font_family',
            ]),
        FormView::make('company.form')
            ->model('res.company')
            ->section('main', 'Company', [
                'name',
                'timezone',
                Field::make('active')->toggle(),
            ])
            ->section('branding', 'Branding & white-label', [
                'app_name',
                'app_tagline',
                'logo_url',
                'logo_url_dark',
                'header_logo_height',
                Field::make('show_header_brand_text')->toggle(),
                'favicon_url',
                'primary_color',
                'font_family',
                'copyright_text',
                'support_email',
                'support_url',
                Field::make('show_powered_by')->toggle(),
                'menu_layout',
            ]),
    );
