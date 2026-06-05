<?php

declare(strict_types=1);

use Velm\Modules\Dashboard\DashboardData;

test('dashboard data builder collects widget specs', function (): void {
    $data = DashboardData::make('demo')
        ->widget(
            id: 'demo_stat',
            title: 'Demo stat',
            model: 'demo.model',
            view: 'velm-ui::dashboard.stat-card',
            resolver: 'Demo\\Widget::resolve',
            sequence: 5,
        );

    $widgets = $data->widgets();

    expect($widgets)->toHaveCount(1)
        ->and($widgets[0]->id)->toBe('demo_stat')
        ->and($widgets[0]->module)->toBe('demo')
        ->and($widgets[0]->model)->toBe('demo.model');
});
