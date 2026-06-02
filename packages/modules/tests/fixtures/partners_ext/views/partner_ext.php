<?php

declare(strict_types=1);

use Velm\Views\Authoring\InheritView;
use Velm\Views\Authoring\ViewOp;

return [
    'VIEW_INHERITS' => [
        InheritView::make('partner.list.ext', 'partners.partner.list', [
            ViewOp::update(['fields', 'name'], ['label' => 'Partner name']),
        ], priority: 25),
    ],
];
