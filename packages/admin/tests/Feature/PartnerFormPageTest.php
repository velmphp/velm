<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Livewire\Livewire;
use Velm\Admin\Pages\CreatePartnerPage;
use Velm\Admin\Pages\EditPartnerPage;
use Velm\Admin\Tests\TestCase;
use Velm\Environment;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('create partner page surfaces validation errors for missing name', function (): void {
    Livewire::actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']))
        ->test(CreatePartnerPage::class)
        ->set('data.name', '')
        ->call('createVelmForm')
        ->assertSet('formError', fn (?string $msg): bool => is_string($msg) && $msg !== '');
});

test('create partner page creates record from arch form', function (): void {
    Livewire::actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']))
        ->test(CreatePartnerPage::class)
        ->set('data.name', 'Created Via Form')
        ->set('data.active', true)
        ->call('createVelmForm')
        ->assertHasNoErrors();

    $match = app(Environment::class)->model('res.partner')->search([['name', '=', 'Created Via Form']])->count();

    expect($match)->toBe(1);
});

test('edit partner page saves arch form changes', function (): void {
    $partnerId = app(Environment::class)->model('res.partner')->create(['name' => 'Before Save'])->ids()[0];

    Livewire::actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']))
        ->test(EditPartnerPage::class, ['record' => $partnerId])
        ->set('data.name', 'After Save')
        ->call('saveVelmForm')
        ->assertHasNoErrors();

    $name = app(Environment::class)->browse('res.partner', [$partnerId])->read()[0]['name'];

    expect($name)->toBe('After Save');
});

test('create partner page prefills relational defaults from query string', function (): void {
    $countryId = app(Environment::class)->model('res.country')->create(['name' => 'Prefill Country', 'code' => 'PC'])->ids()[0];

    Livewire::actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']))
        ->withQueryParams(['country_id' => (string) $countryId])
        ->test(CreatePartnerPage::class)
        ->assertSet('data.country_id', $countryId);
});

test('edit partner page exposes form sections and display name', function (): void {
    $partnerId = app(Environment::class)->model('res.partner')->create(['name' => 'Display Name Co'])->ids()[0];

    $page = Livewire::actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']))
        ->test(EditPartnerPage::class, ['record' => $partnerId]);

    expect($page->instance()->velmFormSections())->not->toBeEmpty()
        ->and($page->instance()->velmRecordDisplayName())->toContain('Display Name');
});

test('edit partner page reports velm form can delete for existing record', function (): void {
    $partnerId = app(Environment::class)->model('res.partner')->create(['name' => 'Can Delete'])->ids()[0];

    $page = Livewire::actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']))
        ->test(EditPartnerPage::class, ['record' => $partnerId]);

    expect($page->instance()->velmFormCanDelete())->toBeTrue();
});

test('edit partner page can delete record from arch form', function (): void {
    $partnerId = app(Environment::class)->model('res.partner')->create(['name' => 'Delete Via Form'])->ids()[0];

    Livewire::actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']))
        ->test(EditPartnerPage::class, ['record' => $partnerId])
        ->call('deleteVelmForm')
        ->assertHasNoErrors();

    expect(app(Environment::class)->model('res.partner')->search([['id', '=', $partnerId]])->count())->toBe(0);
});
