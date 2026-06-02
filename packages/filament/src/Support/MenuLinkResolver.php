<?php

declare(strict_types=1);

namespace Velm\Filament\Support;

final class MenuLinkResolver
{
    public static function url(?string $href): ?string
    {
        $pageClass = ArchPageMap::pageClassForHref($href);

        if ($pageClass === null) {
            return $href;
        }

        return $pageClass::getUrl(panel: 'velm');
    }
}
