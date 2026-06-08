<?php

declare(strict_types=1);

use Velm\Admin\Pages\CompanyListPage;
use Velm\Admin\Pages\StoredViewRecordPage;
use Velm\Admin\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }
});

test('company list page exposes analytics view switcher items', function (): void {
    $switcher = app(CompanyListPage::class)->analyticsViewSwitcher();

    expect($switcher)->not->toBeEmpty();

    $list = collect($switcher)->firstWhere('type', 'list');
    $kanban = collect($switcher)->firstWhere('type', 'kanban');

    expect($list)->not->toBeNull()
        ->and($list['active'])->toBeTrue()
        ->and($list['url'])->toBe(CompanyListPage::getUrl())
        ->and($kanban)->not->toBeNull()
        ->and($kanban['active'])->toBeFalse()
        ->and($kanban['url'])->toContain('/velm/views/base/company.kanban');
});

test('company list open links to display record page not edit', function (): void {
    $page = app(CompanyListPage::class);

    $open = (new ReflectionMethod(CompanyListPage::class, 'openRecordUrl'))
        ->invoke($page, 42);

    $edit = (new ReflectionMethod(CompanyListPage::class, 'editRecordUrl'))
        ->invoke($page, 42);

    expect($open)->toContain('/velm/views/base/company.detail/42')
        ->and($open)->not->toContain('/edit')
        ->and($edit)->toContain('/velm/views/base/company.form/42/edit');
});

test('stored view record page route matches list arch detail view', function (): void {
    expect(StoredViewRecordPage::getUrl([
        'module' => 'base',
        'viewName' => 'company.detail',
        'record' => 1,
    ]))->toContain('/velm/views/base/company.detail/1');
});
