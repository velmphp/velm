<?php

declare(strict_types=1);

namespace Velm\Admin\Support;

use Velm\Admin\Pages\CompanyListPage;
use Velm\Admin\Pages\CreateCompanyPage;
use Velm\Admin\Pages\CreatePartnerPage;
use Velm\Admin\Pages\EditCompanyPage;
use Velm\Admin\Pages\EditPartnerPage;
use Velm\Admin\Pages\PartnerListPage;

final class ArchPageMap
{
    /** @var array<string, class-string> */
    private const VIEW_PAGES = [
        'partners.partner.list' => PartnerListPage::class,
        'base.company.list' => CompanyListPage::class,
    ];

    /** @var array<class-string, string> */
    private const PAGE_ACTIVE_HREF = [
        CreatePartnerPage::class => 'partners.partner.list',
        EditPartnerPage::class => 'partners.partner.list',
        CreateCompanyPage::class => 'base.company.list',
        EditCompanyPage::class => 'base.company.list',
    ];

    /**
     * @return class-string|null
     */
    public static function pageClassForHref(?string $href): ?string
    {
        if ($href === null || $href === '') {
            return null;
        }

        $key = self::viewKeyFromHref($href);

        if ($key === null) {
            return null;
        }

        return self::VIEW_PAGES[$key] ?? null;
    }

    /**
     * @param  class-string  $pageClass
     */
    public static function canonicalHrefForPage(string $pageClass): ?string
    {
        if (isset(self::PAGE_ACTIVE_HREF[$pageClass])) {
            return self::hrefForViewKey(self::PAGE_ACTIVE_HREF[$pageClass]);
        }

        foreach (self::VIEW_PAGES as $key => $class) {
            if ($class === $pageClass) {
                return self::hrefForViewKey($key);
            }
        }

        return null;
    }

    public static function hrefForViewKey(string $viewKey): string
    {
        [$module, $name] = explode('.', $viewKey, 2);

        return "/velm/views/{$module}/{$name}";
    }

    private static function viewKeyFromHref(string $href): ?string
    {
        if (preg_match('#^/velm/views/([^/]+)/([^/]+)$#', $href, $matches) !== 1) {
            return null;
        }

        return $matches[1].'.'.$matches[2];
    }
}
