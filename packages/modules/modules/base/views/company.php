<?php

declare(strict_types=1);

use Velm\Views\Authoring\Field;
use Velm\Views\Authoring\FormView;
use Velm\Views\Authoring\ListView;
use Velm\Views\Authoring\Menus;
use Velm\Views\Data\ViewsData;

$m = new Menus('base');

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
    )
    ->menus(
        $m->group('administration', 'Administration')
            ->icon('building-office')
            ->sequence(20)
            ->children(
                $m->item('companies', 'Companies')
                    ->view('company.list')
                    ->sequence(10),
            ),
    );
