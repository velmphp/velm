<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Livewire\Livewire;
use Velm\Admin\Auth\Login;
use Velm\Admin\Pages\AppsDetailPage;
use Velm\Admin\Pages\AppsPage;
use Velm\Admin\Pages\StoredViewListPage;
use Velm\Admin\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('login page renders without legacy content property', function (): void {
    Livewire::test(Login::class)
        ->assertOk()
        ->assertSee('Sign in', false);
});

test('authenticated apps page renders', function (): void {
    $this->actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']));

    Livewire::test(AppsPage::class)->assertOk();
});

test('authenticated apps detail page renders with single root', function (): void {
    $this->actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']));

    Livewire::test(AppsDetailPage::class, ['name' => 'base'])
        ->assertOk()
        ->assertSee('base', false);
});

test('stored view list page renders partner list', function (): void {
    $this->actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']));

    Livewire::test(StoredViewListPage::class, [
        'module' => 'partners',
        'viewName' => 'partner.list',
    ])
        ->assertOk()
        ->assertSee('Partners');
});
