<?php

declare(strict_types=1);

use Velm\Admin\Pages\CompanyListPage;
use Velm\Admin\Pages\EditPartnerPage;
use Velm\Admin\Pages\PartnerListPage;
use Velm\Admin\Support\ArchPageMap;
use Velm\Admin\Tests\TestCase;

uses(TestCase::class);

test('canonical href maps list and edit pages to menu path', function (): void {
    expect(ArchPageMap::canonicalHrefForPage(PartnerListPage::class))
        ->toBe('/velm/views/partners/partner.list')
        ->and(ArchPageMap::canonicalHrefForPage(EditPartnerPage::class))
        ->toBe('/velm/views/partners/partner.list');
});

test('page class resolves from menu href', function (): void {
    expect(ArchPageMap::pageClassForHref('/velm/views/partners/partner.list'))
        ->toBe(PartnerListPage::class);
});

test('page url resolves for mapped list views', function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }

    expect(ArchPageMap::pageUrlForView('partners', 'partner.list'))
        ->toBe(PartnerListPage::getUrl())
        ->and(ArchPageMap::pageUrlForView('base', 'company.list'))
        ->toBe(CompanyListPage::getUrl())
        ->and(ArchPageMap::pageUrlForView('base', 'company.kanban'))
        ->toBeNull();
});
