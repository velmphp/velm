<?php

declare(strict_types=1);

use Velm\Views\Authoring\Field;
use Velm\Views\Authoring\InheritView;
use Velm\Views\Authoring\Section;
use Velm\Views\Data\ViewsData;

$formLayout = static function (InheritView $view): InheritView {
    return $view
        ->setCols(2)
        ->updateSection('identity', title: 'Contact', cols: 2)
        ->afterField('identity', 'name', Field::make('website'))
        ->removeSection('organization', 'address')
        ->afterSection(
            'identity',
            Section::make('location', 'Location')->cols(2)->fields('company_id', 'country_id'),
        );
};

return ViewsData::make()
    ->inherits(
        $formLayout(
            InheritView::make('partner.form.ext')
                ->extends('partners.partner.form'),
        ),
        $formLayout(
            InheritView::make('partner.detail.ext')
                ->extends('partners.partner.detail'),
        ),
    );
