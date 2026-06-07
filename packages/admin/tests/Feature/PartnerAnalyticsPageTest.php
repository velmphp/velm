<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Livewire\Livewire;
use Velm\Admin\Pages\StoredViewPage;
use Velm\Admin\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }

    $this->actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']));
});

test('partner kanban page renders grouped cards', function (): void {
    $env = app(\Velm\Environment::class);
    $env->model('res.partner')->create(['name' => 'Kanban Partner', 'active' => true]);

    Livewire::test(StoredViewPage::class, [
        'module' => 'partners',
        'viewName' => 'partner.kanban',
    ])
        ->assertOk()
        ->assertSee('Partners')
        ->assertSee('Kanban Partner');
});

test('partner graph page renders bar chart rows', function (): void {
    $env = app(\Velm\Environment::class);
    $country = $env->model('res.country')->create(['name' => 'Belgium', 'code' => 'BE']);
    $env->model('res.partner')->create(['name' => 'Graph Partner', 'country_id' => $country->ids()[0]]);

    Livewire::test(StoredViewPage::class, [
        'module' => 'partners',
        'viewName' => 'partner.graph',
    ])
        ->assertOk()
        ->assertSee('Partners by country')
        ->assertSee('Belgium');
});

test('partner pivot page renders matrix table', function (): void {
    $env = app(\Velm\Environment::class);
    $env->model('res.partner')->create(['name' => 'Pivot Partner', 'is_company' => true, 'active' => true]);

    Livewire::test(StoredViewPage::class, [
        'module' => 'partners',
        'viewName' => 'partner.pivot',
    ])
        ->assertOk()
        ->assertSee('Partner matrix')
        ->assertSee('Yes');
});
