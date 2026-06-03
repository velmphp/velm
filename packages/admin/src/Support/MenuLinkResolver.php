<?php

declare(strict_types=1);

namespace Velm\Admin\Support;

final class MenuLinkResolver
{
    public static function url(?string $href): ?string
    {
        $pageClass = ArchPageMap::pageClassForHref($href);

        if ($pageClass !== null) {
            return $pageClass::getUrl(panel: 'velm');
        }

        $parsed = StoredViewRoutes::parseListHref($href);

        if ($parsed !== null) {
            return StoredViewRoutes::listPageUrl($parsed['module'], $parsed['view']);
        }

        return $href;
    }
}
