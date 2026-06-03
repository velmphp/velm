<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Livewire\Livewire;
use Velm\Admin\Pages\StoredViewEditPage;
use Velm\Admin\Pages\StoredViewRecordPage;
use Velm\Admin\Support\StoredViewRoutes;
use Velm\Admin\Tests\TestCase;
use Velm\Environment;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('detail form shows delete when unlink is allowed', function (): void {
    $env = app(Environment::class);
    $id = (int) $env->model('res.partner')->create(['name' => 'Delete Me', 'active' => true])->ids()[0];

    Livewire::actingAs(new GenericUser(['id' => 1, 'name' => 'Admin', 'email' => 'admin@test']))
        ->test(StoredViewRecordPage::class, [
            'module' => 'partners',
            'viewName' => 'partner.detail',
            'record' => $id,
        ])
        ->assertSee(__('Delete'));
});

test('edit form shows delete and removes record', function (): void {
    $env = app(Environment::class);
    $id = (int) $env->model('res.partner')->create(['name' => 'Edit Delete', 'active' => true])->ids()[0];

    Livewire::actingAs(new GenericUser(['id' => 1, 'name' => 'Admin', 'email' => 'admin@test']))
        ->test(StoredViewEditPage::class, [
            'module' => 'partners',
            'viewName' => 'partner.form',
            'record' => $id,
        ])
        ->call('deleteVelmForm')
        ->assertRedirect(StoredViewRoutes::listPageUrl('partners', 'partner.list'));

    expect($env->model('res.partner')->search([['id', '=', $id]])->count())->toBe(0);
});
