<?php

declare(strict_types=1);

use Velm\Admin\Arch\PivotDataBuilder;
use Velm\Admin\Tests\TestCase;
use Velm\Views\Authoring\PivotView;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('pivot data builder crosses company and active with search filter', function (): void {
    $env = app(\Velm\Environment::class);
    $env->model('res.partner')->create(['name' => 'Data A', 'is_company' => true, 'active' => true]);
    $env->model('res.partner')->create(['name' => 'Data B', 'is_company' => false, 'active' => true]);

    $arch = PivotView::make('partner.pivot')
        ->model('res.partner')
        ->rows(['is_company'])
        ->cols(['active'])
        ->measures(['__count'])
        ->domain([['name', 'ilike', 'Data %']])
        ->toArray();

    $arch = [
        'view_type' => $arch['view_type'],
        'model' => $arch['model'],
        ...$arch['arch'],
    ];

    $grid = (new PivotDataBuilder)->build($arch, $env, ['is_company'], ['active'], ['__count'], 'Data');

    expect($grid['body_rows'])->not->toBeEmpty()
        ->and($grid['header_levels'])->not->toBeEmpty()
        ->and($grid['measure_count'])->toBe(1);
});
