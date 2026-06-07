<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Livewire\Livewire;
use Velm\Admin\Pages\PartnerListPage;
use Velm\Admin\Tests\TestCase;
use Velm\Environment;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('partner list toolbar search filters visible rows', function (): void {
    $env = app(Environment::class);
    $env->model('res.partner')->create(['name' => 'Acme Corp', 'active' => true]);
    $env->model('res.partner')->create(['name' => 'Beta LLC', 'active' => true]);

    $page = Livewire::actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']))
        ->test(PartnerListPage::class)
        ->set('listSearch', 'Acme');

    $rows = $page->instance()->listRecords();

    expect($rows->pluck('name')->all())->toContain('Acme Corp')
        ->and($rows->pluck('name')->all())->not->toContain('Beta LLC');
});

test('partner list toolbar boolean filter and group by update presentation', function (): void {
    Livewire::actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']))
        ->test(PartnerListPage::class)
        ->call('addBooleanListFilter', 'active', true)
        ->call('setListGroupBy', 'active')
        ->assertSet('listGroupBy', 'active')
        ->assertSet('listFiltersPanelOpen', false);
});

test('partner list column visibility can be toggled', function (): void {
    Livewire::actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']))
        ->test(PartnerListPage::class)
        ->set('listColumnsPanelOpen', true)
        ->call('toggleListColumn', 'active')
        ->assertSet('listColumnVisibility.active', false);
});

test('partner list m2o filter and clear query reset toolbar state', function (): void {
    $env = app(Environment::class);
    $countryId = $env->model('res.country')->search(limit: 1)->ids()[0];

    Livewire::actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']))
        ->test(PartnerListPage::class)
        ->call('toggleListFilterField', 'country_id')
        ->set('listM2oQuery.country_id', 'Bel')
        ->call('searchListM2o', 'country_id')
        ->call('addM2oListFilter', 'country_id', $countryId, 'Belgium')
        ->call('removeListFilterChipByField', 'country_id')
        ->call('clearListQuery')
        ->assertSet('listSearch', '')
        ->assertSet('listGroupBy', null);
});

test('partner list exposes row actions and click to open setting', function (): void {
    $page = Livewire::actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']))
        ->test(PartnerListPage::class);

    expect($page->instance()->listClickToOpen())->toBeBool()
        ->and($page->instance()->listRowActions())->toBeArray();
});

test('partner list group by toggle clears when invoked twice', function (): void {
    Livewire::actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']))
        ->test(PartnerListPage::class)
        ->call('toggleListGroupBy', 'active')
        ->assertSet('listGroupBy', 'active')
        ->call('toggleListGroupBy', 'active')
        ->assertSet('listGroupBy', null);
});
