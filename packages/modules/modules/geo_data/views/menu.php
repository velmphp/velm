<?php

declare(strict_types=1);

use Velm\Views\Authoring\Menus;
use Velm\Views\Data\ViewsData;

$m = new Menus('geo_data');

return ViewsData::make()
    ->menus(
        $m->group('geography', 'Geography')
            ->parent('admin', 'settings')
            ->sequence(70)
            ->children(
                $m->item('geography.continents', 'Continents')
                    ->view('continent.list')
                    ->sequence(10),
                $m->item('geography.countries', 'Countries')
                    ->view('country.list')
                    ->sequence(20),
                $m->item('geography.states', 'States / provinces')
                    ->view('state.list')
                    ->sequence(30),
                $m->item('geography.cities', 'Cities')
                    ->view('city.list')
                    ->sequence(40),
            ),
    );
