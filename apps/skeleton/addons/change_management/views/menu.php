<?php

declare(strict_types=1);

use Velm\Views\Authoring\Menus;
use Velm\Views\Data\ViewsData;

$m = new Menus('change_management');

return ViewsData::make()
    ->menus(
        $m->group('change_mgmt', 'Change management')
            ->icon('wrench-screwdriver')
            ->sequence(35)
            ->children(
                $m->item('change_requests', 'Change requests')
                    ->view('change.list')
                    ->icon('clipboard-document-list')
                    ->sequence(10),
            ),
    );
