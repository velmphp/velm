<?php

declare(strict_types=1);

use Livewire\Livewire;
use Velm\Admin\Pages\StoredViewListPage;
use Velm\Admin\Pages\StoredViewRecordPage;
use Velm\Admin\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('server actions list page renders for administrators', function (): void {
    Livewire::test(StoredViewListPage::class, [
        'module' => 'base',
        'viewName' => 'server.action.list',
    ])
        ->assertOk()
        ->assertSee('Server actions');
});

test('scheduled actions list page renders created cron row', function (): void {
    $env = app(\Velm\Environment::class);
    $actionId = $env->model('ir.actions.server')->create([
        'name' => 'Nightly partner archive',
        'model' => 'res.partner',
        'action_type' => 'write',
        'vals_json' => '{"active": false}',
    ])->ids()[0];
    $env->model('ir.cron')->create([
        'name' => 'Nightly partner archive',
        'action_id' => $actionId,
        'interval_number' => 1,
        'interval_type' => 'days',
        'active' => true,
    ]);

    Livewire::test(StoredViewListPage::class, [
        'module' => 'base',
        'viewName' => 'cron.list',
    ])
        ->assertOk()
        ->assertSee('Scheduled actions')
        ->assertSee('Nightly partner archive');
});

test('server action detail page renders code payload widget', function (): void {
    $env = app(\Velm\Environment::class);
    $actionId = $env->model('ir.actions.server')->create([
        'name' => 'Test write partners',
        'model' => 'res.partner',
        'action_type' => 'write',
        'vals_json' => '{"active": false}',
    ])->ids()[0];

    Livewire::test(StoredViewRecordPage::class, [
        'module' => 'base',
        'viewName' => 'server.action.detail',
        'record' => $actionId,
    ])
        ->assertOk()
        ->assertSee('Test write partners')
        ->assertSee('res.partner')
        ->assertSee('data-pv-code-display', false);
});
