<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Livewire\Livewire;
use Velm\Admin\Auth\Login;
use Velm\Admin\Pages\AppsDetailPage;
use Velm\Admin\Pages\AppsPage;
use Velm\Admin\Pages\CompanyListPage;
use Velm\Admin\Pages\CreateCompanyPage;
use Velm\Admin\Pages\CreatePartnerPage;
use Velm\Admin\Pages\EditPartnerPage;
use Velm\Admin\Pages\FileLibraryPage;
use Velm\Admin\Pages\PartnerListPage;
use Velm\Admin\Pages\StoredViewCreatePage;
use Velm\Admin\Pages\StoredViewEditPage;
use Velm\Admin\Pages\StoredViewListPage;
use Velm\Admin\Pages\StoredViewPage;
use Velm\Admin\Pages\StoredViewRecordPage;
use Velm\Admin\Pages\WorkflowBuilderPage;
use Velm\Admin\Pages\WorkflowInboxPage;
use Velm\Admin\Tests\TestCase;
use Velm\Framework\VelmManager;

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

test('partner and company arch pages render for authenticated users', function (): void {
    $this->actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']));

    Livewire::test(PartnerListPage::class)->assertOk();
    Livewire::test(CreatePartnerPage::class)->assertOk();
    Livewire::test(CompanyListPage::class)->assertOk();
    Livewire::test(CreateCompanyPage::class)->assertOk();
});

test('edit company page renders for existing record', function (): void {
    $this->actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']));
    $companyId = app(\Velm\Environment::class)->model('res.company')->search(limit: 1)->ids()[0];

    Livewire::test(\Velm\Admin\Pages\EditCompanyPage::class, ['record' => $companyId])->assertOk();
});

test('stored view create page renders with module and view params', function (): void {
    $this->actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']));

    Livewire::test(StoredViewCreatePage::class, [
        'module' => 'partners',
        'viewName' => 'partner.form',
    ])->assertOk();
});

test('edit partner page renders for existing record', function (): void {
    $this->actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']));
    $partnerId = app(\Velm\Environment::class)->model('res.partner')->create(['name' => 'Edit Me'])->ids()[0];

    Livewire::test(EditPartnerPage::class, ['record' => $partnerId])->assertOk();
});

test('workflow inbox and builder pages render when workflow module installed', function (): void {
    app(VelmManager::class)->install('workflow');
    $this->actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']));

    Livewire::test(WorkflowInboxPage::class)->assertOk();
    Livewire::test(WorkflowBuilderPage::class)->assertOk();
});

test('file library page renders when file manager module installed', function (): void {
    app(VelmManager::class)->install('file_manager');
    $this->actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']));

    Livewire::test(FileLibraryPage::class)->assertOk();
});

test('stored view partner pages render for list form and detail', function (): void {
    $this->actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']));
    $partnerId = app(\Velm\Environment::class)->model('res.partner')->create(['name' => 'Stored View Partner'])->ids()[0];

    Livewire::test(StoredViewPage::class, [
        'module' => 'partners',
        'viewName' => 'partner.list',
    ])->assertOk();

    Livewire::test(StoredViewRecordPage::class, [
        'module' => 'partners',
        'viewName' => 'partner.detail',
        'record' => $partnerId,
    ])->assertOk();

    Livewire::test(StoredViewEditPage::class, [
        'module' => 'partners',
        'viewName' => 'partner.form',
        'record' => $partnerId,
    ])->assertOk();
});

test('stored view edit page uses embed form trait for partner record', function (): void {
    $this->actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']));
    $partnerId = app(\Velm\Environment::class)->model('res.partner')->create(['name' => 'Embed Partner'])->ids()[0];

    Livewire::withQueryParams(['embed' => '1'])
        ->test(StoredViewEditPage::class, [
            'module' => 'partners',
            'viewName' => 'partner.form',
            'record' => $partnerId,
        ])
        ->assertOk();

    $url = \Velm\Admin\Support\StoredViewRoutes::recordPageUrl('partners', 'partner.form', $partnerId);

    expect($url)->toContain('partners')
        ->and($url)->toContain((string) $partnerId);
});
