<?php

declare(strict_types=1);

use Velm\Views\Authoring\InheritView;
use Velm\Views\Data\ViewsData;

return ViewsData::make()
    ->inherits(
        InheritView::make('partner.list.ext')
            ->extends('partners.partner.list')
            ->updateColumn('name', label: 'Partner name'),
    );
