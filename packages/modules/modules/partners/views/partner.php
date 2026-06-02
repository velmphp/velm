<?php

declare(strict_types=1);

use Velm\Views\Authoring\Field;
use Velm\Views\Authoring\FormView;
use Velm\Views\Authoring\ListView;

return [
    'VIEWS' => [
        ListView::make('partner.list')
            ->model('res.partner')
            ->title('Partners')
            ->formView('partner.form')
            ->columns([
                'name',
                Field::make('is_company')->toggle(),
                'company_id',
                'country_id',
                Field::make('active')->toggle(),
            ]),

        FormView::make('partner.form')
            ->model('res.partner')
            ->section('identity', 'Identity', [
                'name',
                Field::make('is_company')->toggle(),
                Field::make('active')->toggle(),
            ])
            ->section('organization', 'Organization', ['company_id'])
            ->section('address', 'Address', ['country_id']),
    ],
];
