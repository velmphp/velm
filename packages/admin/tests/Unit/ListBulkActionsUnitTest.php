<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Livewire\Livewire;
use Velm\Admin\Tests\Support\ArchListProbe;
use Velm\Admin\Tests\TestCase;
use Velm\Environment;
use Velm\Views\Authoring\Action;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('list bulk actions ignore invalid bulk_actions arch values', function (): void {
    $page = Livewire::actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']))
        ->test(ArchListProbe::class);

    $page->instance()->probeArch = [
        'model' => 'res.partner',
        'fields' => [['name' => 'name']],
        'bulk_actions' => 'invalid',
    ];

    expect($page->instance()->listBulkActions())->toHaveCount(1)
        ->and($page->instance()->listBulkActions()[0]['wire'])->toBe('delete');
});

test('list bulk actions can deselect all rows on page', function (): void {
    $env = app(Environment::class);
    $idA = (int) $env->model('res.partner')->create(['name' => 'Deselect A'])->ids()[0];
    $idB = (int) $env->model('res.partner')->create(['name' => 'Deselect B'])->ids()[0];

    $page = Livewire::actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']))
        ->test(ArchListProbe::class)
        ->call('toggleListSelectAllOnPage')
        ->call('toggleListSelectAllOnPage');

    expect($page->instance()->listSelectedCount())->toBe(0)
        ->and($page->instance()->listAllPageSelected())->toBeFalse();
});

test('list bulk wire action ignores unknown handlers and empty selection', function (): void {
    $env = app(Environment::class);
    $id = (int) $env->model('res.partner')->create(['name' => 'Still here'])->ids()[0];

    $page = Livewire::actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']))
        ->test(ArchListProbe::class);

    $page->instance()->probeArch = [
        'model' => 'res.partner',
        'fields' => [['name' => 'name']],
        'bulk_actions' => [
            Action::make('Custom')->wire('noop')->perm('write')->toArray(),
        ],
    ];

    $page->set('listSelectedIds', [$id])
        ->call('runListBulkWireAction', 'custom')
        ->call('runListBulkWireAction', 'missing');

    expect($env->model('res.partner')->search([['id', '=', $id]])->count())->toBe(1);
});

test('list selection clears when pagination page changes', function (): void {
    $env = app(Environment::class);

    for ($i = 1; $i <= 12; $i++) {
        $env->model('res.partner')->create(['name' => "Page {$i}"]);
    }

    $page = Livewire::actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']))
        ->test(ArchListProbe::class)
        ->set('listPerPage', 10)
        ->set('listSelectedIds', [1, 2, 3])
        ->call('nextPage');

    expect($page->instance()->listSelectedCount())->toBe(0);
});

test('list selection normalizes invalid ids', function (): void {
    $page = Livewire::actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']))
        ->test(ArchListProbe::class)
        ->set('listSelectedIds', [0, -1, 2, 2, '3']);

    expect($page->instance()->listSelectedCount())->toBe(2);
});
