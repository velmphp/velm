<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Livewire\Livewire;
use Velm\Admin\Pages\StoredViewListPage;
use Velm\Admin\Tests\TestCase;
use Velm\Environment;
use Velm\Views\Authoring\Action;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('list bulk actions include default delete when unlink is allowed', function (): void {
    $page = Livewire::actingAs(new GenericUser(['id' => 1, 'name' => 'Admin', 'email' => 'admin@test']))
        ->test(StoredViewListPage::class, [
            'module' => 'partners',
            'viewName' => 'partner.list',
        ]);

    $actions = $page->instance()->listBulkActions();

    expect(collect($actions)->pluck('wire')->all())->toContain('delete')
        ->and($page->instance()->listShowsSelection())->toBeTrue();
});

test('select all on page selects current page record ids', function (): void {
    $env = app(Environment::class);
    $env->model('res.partner')->create(['name' => 'Bulk A', 'active' => true]);
    $env->model('res.partner')->create(['name' => 'Bulk B', 'active' => true]);

    $page = Livewire::actingAs(new GenericUser(['id' => 1, 'name' => 'Admin', 'email' => 'admin@test']))
        ->test(StoredViewListPage::class, [
            'module' => 'partners',
            'viewName' => 'partner.list',
        ])
        ->call('toggleListSelectAllOnPage');

    expect($page->instance()->listSelectedCount())->toBeGreaterThan(0)
        ->and($page->instance()->listAllPageSelected())->toBeTrue();
});

test('bulk delete removes selected records', function (): void {
    $env = app(Environment::class);
    $idA = (int) $env->model('res.partner')->create(['name' => 'Bulk delete A', 'active' => true])->ids()[0];
    $idB = (int) $env->model('res.partner')->create(['name' => 'Bulk delete B', 'active' => true])->ids()[0];

    Livewire::actingAs(new GenericUser(['id' => 1, 'name' => 'Admin', 'email' => 'admin@test']))
        ->test(StoredViewListPage::class, [
            'module' => 'partners',
            'viewName' => 'partner.list',
        ])
        ->set('listSelectedIds', [$idA, $idB])
        ->call('runListBulkWireAction', 'delete')
        ->assertHasNoErrors();

    expect($env->model('res.partner')->search([['id', 'in', [$idA, $idB]]])->count())->toBe(0);
});

test('partners list arch includes export selected bulk action', function (): void {
    $env = app(Environment::class);
    $arch = app(\Velm\Views\ViewRegistry::class)->arch($env, 'partners', 'partner.list');

    expect($arch['bulk_actions'] ?? null)->toBeArray()
        ->and(collect($arch['bulk_actions'])->pluck('label')->all())->toContain('Export selected');
});

test('action builder serializes wire handler for bulk actions', function (): void {
    $action = Action::make('Delete')
        ->wire('delete')
        ->perm('unlink')
        ->toArray();

    expect($action['wire'])->toBe('delete');
});
