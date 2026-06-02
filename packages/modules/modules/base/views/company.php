<?php

declare(strict_types=1);

use Velm\Views\Authoring\Field;
use Velm\Views\Authoring\FormView;
use Velm\Views\Authoring\ListView;
use Velm\Views\Data\ViewsData;

return ViewsData::make()
    ->views(
        ListView::make('company.list')
            ->model('res.company')
            ->title('Companies')
            ->formView('company.form')
            ->columns([
                'name',
                Field::make('active')->toggle(),
            ]),
        FormView::make('company.form')
            ->model('res.company')
            ->section('main', 'Company', [
                'name',
                Field::make('active')->toggle(),
            ]),
    );
