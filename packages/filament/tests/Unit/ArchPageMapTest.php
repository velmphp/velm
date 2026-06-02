<?php

declare(strict_types=1);

use Velm\Filament\Pages\EditPartnerPage;
use Velm\Filament\Pages\PartnerListPage;
use Velm\Filament\Support\ArchPageMap;

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
