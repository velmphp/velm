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

test('dashboard data rejects empty module name', function (): void {
    expect(fn () => DashboardData::make(''))
        ->toThrow(InvalidArgumentException::class, 'module name must not be empty');
});

test('dashboard data rejects incomplete widget definitions', function (): void {
    expect(fn () => DashboardData::make('demo')->widget(
        id: '',
        title: 'Title',
        model: 'demo.model',
        view: 'velm-ui::dashboard.stat-card',
        resolver: 'Demo\\Widget::resolve',
    ))->toThrow(InvalidArgumentException::class, 'required');
});

test('dashboard data rejects invalid widget size and perm', function (): void {
    $builder = DashboardData::make('demo');

    expect(fn () => $builder->widget(
        id: 'x',
        title: 'X',
        model: 'demo.model',
        view: 'velm-ui::dashboard.stat-card',
        resolver: 'Demo\\Widget::resolve',
        size: 'wide',
    ))->toThrow(InvalidArgumentException::class, 'size');

    expect(fn () => $builder->widget(
        id: 'y',
        title: 'Y',
        model: 'demo.model',
        view: 'velm-ui::dashboard.stat-card',
        resolver: 'Demo\\Widget::resolve',
        perm: 'delete',
    ))->toThrow(InvalidArgumentException::class, 'perm');
});
