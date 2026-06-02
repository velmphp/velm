<?php

declare(strict_types=1);

use Velm\Views\Authoring\InheritView;
use Velm\Views\Authoring\ViewOp;
use Velm\Views\Data\ViewsData;

return ViewsData::make()
    ->inherits(
        InheritView::make('partner.list.ext', 'partners.partner.list', [
            ViewOp::update(['fields', 'name'], ['label' => 'Partner name']),
        ], priority: 25),
    );
