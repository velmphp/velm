<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Livewire\Livewire;
use Velm\Admin\Pages\AppsDetailPage;
use Velm\Admin\Pages\AppsPage;
use Velm\Admin\Tests\TestCase;
use Velm\Framework\VelmManager;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('apps page catalog summary counts bundled modules', function (): void {
    $page = Livewire::actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']))
        ->test(AppsPage::class);

    $summary = $page->instance()->catalogSummary();

    expect($summary['total'])->toBeGreaterThan(3)
        ->and($summary['installed'])->toBeGreaterThan(0)
        ->and($page->instance()->catalogCategories())->not->toBeEmpty();
});

test('apps page can install and sync optional module', function (): void {
    Livewire::actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']))
        ->test(AppsPage::class)
        ->call('installModule', 'workflow')
        ->assertHasNoErrors();

    expect(app(VelmManager::class)->environment()->registry->has('workflow.definition'))->toBeTrue();

    Livewire::actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']))
        ->test(AppsPage::class)
        ->call('syncModule', 'workflow')
        ->assertHasNoErrors();
});

test('apps detail page sync action runs for installed module', function (): void {
    app(VelmManager::class)->install('workflow');

    Livewire::actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']))
        ->test(AppsDetailPage::class, ['name' => 'workflow'])
        ->call('syncModule')
        ->assertHasNoErrors();
});

test('apps page upgrade and uninstall hooks run for installed optional module', function (): void {
    app(VelmManager::class)->install('workflow');

    Livewire::actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']))
        ->test(AppsPage::class)
        ->call('upgradeModule', 'workflow')
        ->assertHasNoErrors();

    Livewire::actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']))
        ->test(AppsPage::class)
        ->call('uninstallModule', 'workflow')
        ->assertHasNoErrors();
});
