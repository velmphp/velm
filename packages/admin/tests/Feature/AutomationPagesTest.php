<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Livewire\Livewire;
use Velm\Admin\Pages\StoredViewCreatePage;
use Velm\Admin\Pages\StoredViewEditPage;
use Velm\Admin\Pages\StoredViewListPage;
use Velm\Admin\Pages\StoredViewRecordPage;
use Velm\Admin\Tests\TestCase;
use Velm\Cron\CronJob;

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

test('server action create and edit pages render for administrators', function (): void {
    $this->actingAs(new GenericUser(['id' => 1, 'name' => 'Admin', 'email' => 'admin@test']));

    Livewire::test(StoredViewCreatePage::class, [
        'module' => 'base',
        'viewName' => 'server.action.form',
    ])
        ->assertOk()
        ->assertSee('Server action');

    $env = app(\Velm\Environment::class);
    $actionId = $env->model('ir.actions.server')->create([
        'name' => 'Editable action',
        'model' => 'res.partner',
        'action_type' => 'write',
        'vals_json' => '{}',
    ])->ids()[0];

    Livewire::test(StoredViewEditPage::class, [
        'module' => 'base',
        'viewName' => 'server.action.form',
        'record' => $actionId,
    ])
        ->assertOk()
        ->assertSee('Editable action');
});

test('scheduled action detail page shows last and next call fields', function (): void {
    $env = app(\Velm\Environment::class);
    $actionId = $env->model('ir.actions.server')->create([
        'name' => 'Detail cron action',
        'model' => 'res.partner',
        'action_type' => 'write',
        'vals_json' => '{}',
    ])->ids()[0];
    $cronId = $env->model('ir.cron')->create([
        'name' => 'Detail cron job',
        'action_id' => $actionId,
        'interval_number' => 2,
        'interval_type' => 'hours',
        'nextcall' => '2030-01-01 00:00:00',
        'lastcall' => '2029-12-31 00:00:00',
        'active' => true,
    ])->ids()[0];

    Livewire::test(StoredViewRecordPage::class, [
        'module' => 'base',
        'viewName' => 'cron.detail',
        'record' => $cronId,
    ])
        ->assertOk()
        ->assertSee('Detail cron job')
        ->assertSee('2030-01-01 00:00:00')
        ->assertSee('2029-12-31 00:00:00');
});

test('user defined scheduled action executes linked server action', function (): void {
    $env = app(\Velm\Environment::class);
    $partnerId = (int) $env->model('res.partner')->create([
        'name' => 'Cron target',
        'active' => true,
    ])->ids()[0];
    $actionId = $env->model('ir.actions.server')->create([
        'name' => 'Deactivate cron target',
        'model' => 'res.partner',
        'action_type' => 'write',
        'vals_json' => '{"active": false}',
    ])->ids()[0];
    $env->model('ir.cron')->create([
        'name' => 'Deactivate cron target',
        'action_id' => $actionId,
        'interval_number' => 1,
        'interval_type' => 'hours',
        'nextcall' => '2000-01-01 00:00:00',
        'active' => true,
    ]);

    $executed = CronJob::runDue($env);

    expect($executed)->toContain('Deactivate cron target')
        ->and($env->model('res.partner')->search([['id', '=', $partnerId]])->read()[0]['active'])->toBeFalse();
});
