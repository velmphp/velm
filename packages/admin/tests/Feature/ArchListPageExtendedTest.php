<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Livewire\Livewire;
use Velm\Admin\Pages\PartnerListPage;
use Velm\Admin\Support\ListPageSize;
use Velm\Admin\Tests\Support\ArchListProbe;
use Velm\Admin\Tests\TestCase;
use Velm\Environment;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('arch list page falls back to parent title when arch omits one', function (): void {
    $page = Livewire::actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']))
        ->test(ArchListProbe::class)
        ->set('probeArch', [
            'model' => 'res.partner',
            'fields' => [['name' => 'name']],
        ]);

    expect($page->instance()->getTitle())->toBeString()->not->toBe('');
});

test('arch list page toggles boolean fields and ignores invalid deletes', function (): void {
    $env = app(Environment::class);
    $partnerId = $env->model('res.partner')->create(['name' => 'Toggle Me', 'active' => true])->ids()[0];

    Livewire::actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']))
        ->test(PartnerListPage::class)
        ->call('updateListToggle', $partnerId, 'active', false)
        ->call('deleteListRecord', 0)
        ->assertHasNoErrors();

    expect($env->browse('res.partner', [$partnerId])->read()[0]['active'])->toBeFalse();

    $probe = Livewire::actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']))
        ->test(ArchListProbe::class)
        ->instance();
    $probe->probeArch = ['model' => '', 'fields' => [['name' => 'name']]];
    $probe->deleteListRecord(5);
});

test('arch list page groups records by boolean and relation fields', function (): void {
    $env = app(Environment::class);
    $countryId = $env->model('res.country')->create(['name' => 'Grouped Country', 'code' => 'GC'])->ids()[0];
    $env->model('res.partner')->create(['name' => 'Grouped Active', 'active' => true, 'country_id' => $countryId]);
    $env->model('res.partner')->create(['name' => 'Grouped Inactive', 'active' => false]);

    $activeGroups = Livewire::actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']))
        ->test(PartnerListPage::class)
        ->call('setListGroupBy', 'active')
        ->instance()
        ->groupedListRecords();

    expect(collect($activeGroups)->pluck('label'))->toContain('Yes', 'No');

    $countryGroups = Livewire::actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']))
        ->test(PartnerListPage::class)
        ->call('setListGroupBy', 'country_id')
        ->instance()
        ->groupedListRecords();

    expect($countryGroups)->not->toBeEmpty();

    $unknownGroups = Livewire::actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']))
        ->test(PartnerListPage::class)
        ->call('setListGroupBy', 'unknown_field')
        ->instance()
        ->groupedListRecords();

    expect($unknownGroups)->not->toBeEmpty();

    $groupLabel = new ReflectionMethod(PartnerListPage::class, 'groupLabel');
    $groupLabel->setAccessible(true);
    $page = app(PartnerListPage::class);
    $env = app(Environment::class);
    $textHeader = [
        'name' => 'name',
        'label' => 'Name',
        'filter_kind' => 'text',
        'group_kind' => 'none',
        'comodel' => null,
        'visible_default' => true,
    ];

    expect($groupLabel->invoke($page, null, 'Raw value', $env))->toBe('Raw value')
        ->and($groupLabel->invoke($page, $textHeader, 'Acme', $env))->toBe('Acme');
});

test('arch list page paginates fetched records', function (): void {
    $page = Livewire::actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']))
        ->test(PartnerListPage::class)
        ->set('listPerPage', 25);

    $paginator = $page->instance()->paginatedListRecords();

    expect($paginator->perPage())->toBe(25);
});

test('arch list page shows all records when page size is all', function (): void {
    $env = app(\Velm\Environment::class);

    for ($i = 1; $i <= 5; $i++) {
        $env->model('res.partner')->create(['name' => "All Size {$i}", 'active' => true]);
    }

    $paginator = Livewire::actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']))
        ->test(PartnerListPage::class)
        ->set('listPerPage', ListPageSize::ALL)
        ->instance()
        ->paginatedListRecords();

    expect($paginator->hasPages())->toBeFalse()
        ->and($paginator->count())->toBeGreaterThanOrEqual(5)
        ->and($paginator->total())->toBe($paginator->count());
});

test('arch list page uses configured pagination view', function (): void {
    config(['velm.list_pagination' => 'full']);

    $page = Livewire::actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']))
        ->test(PartnerListPage::class);

    expect($page->instance()->listPaginationStyle())->toBe('full')
        ->and($page->instance()->listPaginationView())->toBe('velm-ui::pagination.full');
});
