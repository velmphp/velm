<?php

declare(strict_types=1);

namespace Velm\Filament\Support;

use Velm\Filament\Pages\CompanyListPage;
use Velm\Filament\Pages\PartnerListPage;

final class ArchPageMap
{
    /** @var array<string, class-string> */
    private const VIEW_PAGES = [
        'partners.partner.list' => PartnerListPage::class,
        'base.company.list' => CompanyListPage::class,
    ];

    /**
     * @return class-string|null
     */
    public static function pageClassForHref(?string $href): ?string
    {
        if ($href === null || $href === '') {
            return null;
        }

        if (preg_match('#^/velm/views/([^/]+)/([^/]+)$#', $href, $matches) !== 1) {
            return null;
        }

        $key = $matches[1].'.'.$matches[2];

        return self::VIEW_PAGES[$key] ?? null;
    }
}
