<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Livewire\Livewire;
use Velm\Admin\Pages\StoredViewRecordPage;
use Velm\Admin\Tests\TestCase;
use Velm\Framework\VelmManager;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }

    app(VelmManager::class)->install('workflow');
});

test('stored view record exposes workflow context for partner detail', function (): void {
    $env = app(\Velm\Environment::class);
    $partnerId = $env->model('res.partner')->create(['name' => 'Workflow Partner'])->ids()[0];

    $page = Livewire::actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']))
        ->test(StoredViewRecordPage::class, [
            'module' => 'partners',
            'viewName' => 'partner.detail',
            'record' => $partnerId,
        ]);

    expect($page->instance()->velmWorkflowModel())->toBe('res.partner')
        ->and($page->instance()->velmWorkflowRecordId())->toBe($partnerId)
        ->and($page->instance()->velmWorkflowContext())->toBeArray();
});

test('workflow context returns null without valid model or record', function (): void {
    $page = Livewire::actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']))
        ->test(StoredViewRecordPage::class, [
            'module' => 'partners',
            'viewName' => 'partner.detail',
            'record' => 1,
        ]);

    $page->set('record', 0);

    expect($page->instance()->velmWorkflowContext())->toBeNull();
});

test('workflow sidebar is enabled only for models with a workflow definition', function (): void {
    $env = app(\Velm\Environment::class);
    $partnerId = $env->model('res.partner')->create(['name' => 'Workflow Partner'])->ids()[0];
    $companyId = $env->model('res.company')->search(limit: 1)->ids()[0];

    $partnerPage = Livewire::actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']))
        ->test(StoredViewRecordPage::class, [
            'module' => 'partners',
            'viewName' => 'partner.detail',
            'record' => $partnerId,
        ]);

    $companyPage = Livewire::actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']))
        ->test(StoredViewRecordPage::class, [
            'module' => 'base',
            'viewName' => 'company.detail',
            'record' => $companyId,
        ]);

    expect($partnerPage->instance()->velmWorkflowEnabled())->toBeTrue()
        ->and($companyPage->instance()->velmWorkflowEnabled())->toBeFalse();
});
