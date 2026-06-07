<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Livewire\Livewire;
use Velm\Admin\Pages\AppsDetailPage;
use Velm\Admin\Pages\AppsPage;
use Velm\Admin\Tests\TestCase;
use Velm\Environment;
use Velm\Framework\VelmManager;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('apps page install rejects unknown modules', function (): void {
    Livewire::actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']))
        ->test(AppsPage::class)
        ->call('installModule', 'module_that_does_not_exist')
        ->assertHasNoErrors();
});

test('apps page install surfaces installer exceptions', function (): void {
    Livewire::actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']))
        ->test(AppsPage::class)
        ->call('installModule', 'geo_data')
        ->call('installModule', 'geo_data')
        ->assertHasNoErrors();
});

test('apps page module actions surface installer errors', function (): void {
    Livewire::actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']))
        ->test(AppsPage::class)
        ->call('syncModule', 'module_that_does_not_exist')
        ->call('upgradeModule', 'module_that_does_not_exist')
        ->call('uninstallModule', 'module_that_does_not_exist')
        ->assertHasNoErrors();
});

test('apps page module management requires superuser', function (): void {
    $base = app(Environment::class);
    $userId = $base->model('res.users')->create([
        'name' => 'Regular',
        'email' => 'regular@test',
        'password' => 'x',
    ])->ids()[0];

    app()->instance(Environment::class, new Environment(
        $base->connection,
        $base->registry,
        $userId,
    ));

    Livewire::actingAs(new GenericUser(['id' => $userId, 'email' => 'regular@test']))
        ->test(AppsPage::class)
        ->call('installModule', 'geo_data')
        ->assertStatus(403);
});

test('apps detail page aborts for unknown module and runs lifecycle actions', function (): void {
    app(VelmManager::class)->install('geo_data');

    Livewire::actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']))
        ->test(AppsDetailPage::class, ['name' => 'missing_module_xyz'])
        ->assertStatus(404);

    Livewire::actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']))
        ->test(AppsDetailPage::class, ['name' => 'geo_data'])
        ->call('installModule')
        ->call('upgradeModule')
        ->call('uninstallModule')
        ->assertHasNoErrors();
});
