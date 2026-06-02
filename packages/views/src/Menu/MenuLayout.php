<?php

declare(strict_types=1);

namespace Velm\Views\Menu;

final class MenuLayout
{
    public const SIDEBAR = 'sidebar';

    public const APPS = 'apps';

    /** @var array<string, string> */
    private const ALIASES = [
        'odoo' => self::APPS,
    ];

    public static function resolve(?string $contextValue = null): string
    {
        $slug = self::normalize($contextValue);

        if ($slug !== null) {
            return $slug;
        }

        $slug = self::normalize(env('VELM_MENU_LAYOUT'));

        if ($slug !== null) {
            return $slug;
        }

        return self::APPS;
    }

    public static function normalize(?string $raw): ?string
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        $slug = strtolower(trim($raw));
        $slug = self::ALIASES[$slug] ?? $slug;

        return in_array($slug, [self::SIDEBAR, self::APPS], true) ? $slug : null;
    }
}
