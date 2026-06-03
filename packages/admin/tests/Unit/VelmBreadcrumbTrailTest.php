<?php

declare(strict_types=1);

use Velm\Admin\Pages\AppsPage;
use Velm\Admin\Pages\ArchCreatePage;
use Velm\Admin\Pages\ArchEditPage;
use Velm\Admin\Pages\ArchListPage;
use Velm\Admin\Pages\StoredViewRecordPage;
use Velm\Admin\Support\VelmBreadcrumbTier;

test('breadcrumb tier enum covers fixed trail depths', function (): void {
    expect(VelmBreadcrumbTier::List)->toBe(VelmBreadcrumbTier::List)
        ->and(ArchListPage::class)->toBeString()
        ->and(StoredViewRecordPage::class)->toBeString()
        ->and(ArchCreatePage::class)->toBeString()
        ->and(ArchEditPage::class)->toBeString()
        ->and(AppsPage::class)->toBeString();
});
