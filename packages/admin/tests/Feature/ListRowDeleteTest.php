<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Livewire\Livewire;
use Velm\Admin\Pages\StoredViewListPage;
use Velm\Admin\Tests\TestCase;
use Velm\Environment;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('list row actions include delete when unlink is allowed', function (): void {
    $page = Livewire::actingAs(new GenericUser(['id' => 1, 'name' => 'Admin', 'email' => 'admin@test']))
        ->test(StoredViewListPage::class, [
            'module' => 'partners',
            'viewName' => 'partner.list',
        ]);

    $actions = $page->instance()->listRowActions();

    expect(collect($actions)->pluck('action')->all())->toContain('delete');
});

test('delete list record removes row when unlink is allowed', function (): void {
    $env = app(Environment::class);
    $id = (int) $env->model('res.partner')->create(['name' => 'To Delete', 'active' => true])->ids()[0];

    Livewire::actingAs(new GenericUser(['id' => 1, 'name' => 'Admin', 'email' => 'admin@test']))
        ->test(StoredViewListPage::class, [
            'module' => 'partners',
            'viewName' => 'partner.list',
        ])
        ->call('deleteListRecord', $id)
        ->assertHasNoErrors();

    $remaining = $env->model('res.partner')->search([['id', '=', $id]])->count();

    expect($remaining)->toBe(0);
});
