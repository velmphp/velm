<?php

declare(strict_types=1);

use Livewire\Livewire;
use Velm\Admin\Tests\Support\ReconcileUiProbe;
use Velm\Admin\Tests\TestCase;
use Velm\Modules\AppsCatalog;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('reconcile ui sync runs when views drift from disk', function (): void {
    $env = app(\Velm\Environment::class);
    $listView = $env->model('ir.ui.view')->search([
        ['module', '=', 'partners'],
        ['name', '=', 'partner.list'],
    ]);
    $row = $listView->read()[0];
    $arch = json_decode((string) $row['arch'], true, flags: JSON_THROW_ON_ERROR);
    $arch['title'] = 'Partners (drifted for reconcile test)';
    $listView->write(['arch' => json_encode($arch, JSON_THROW_ON_ERROR)]);

    expect((new AppsCatalog)->entry(config('velm.addon_paths'), 'partners')['has_ui_sync'])->toBeTrue();

    Livewire::test(ReconcileUiProbe::class)->call('run', 'partners');

    expect((new AppsCatalog)->entry(config('velm.addon_paths'), 'partners')['has_ui_sync'])->toBeFalse();
});

test('reconcile ui resolves module from velmViewModule when omitted', function (): void {
    Livewire::test(ReconcileUiProbe::class)->call('run')->assertOk();
});

test('reconcile ui no-ops for empty module addon paths and invalid config', function (): void {
    Livewire::test(ReconcileUiProbe::class)->call('run', '')->assertOk();
    Livewire::test(ReconcileUiProbe::class)->call('run')->assertOk();

    config(['velm.addon_paths' => []]);
    Livewire::test(ReconcileUiProbe::class)->call('run', 'partners')->assertOk();

    config(['velm.addon_paths' => 'not-an-array']);
    Livewire::test(ReconcileUiProbe::class)->call('run', 'partners')->assertOk();
});
