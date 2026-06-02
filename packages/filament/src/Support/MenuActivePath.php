<?php

declare(strict_types=1);

namespace Velm\Filament\Support;

use Illuminate\Http\Request;

final class MenuActivePath
{
    public static function forRequest(Request $request): ?string
    {
        $pageClass = self::pageClassFromRequest($request);

        if ($pageClass === null) {
            return null;
        }

        return ArchPageMap::canonicalHrefForPage($pageClass);
    }

    /**
     * @return class-string|null
     */
    private static function pageClassFromRequest(Request $request): ?string
    {
        $route = $request->route();

        if ($route === null) {
            return null;
        }

        $uses = $route->getAction('uses');

        if (! is_string($uses)) {
            return null;
        }

        if (! str_contains($uses, '@')) {
            return null;
        }

        [$class] = explode('@', $uses, 2);

        if (! is_string($class) || ! class_exists($class)) {
            return null;
        }

        if (ArchPageMap::canonicalHrefForPage($class) !== null) {
            return $class;
        }

        return null;
    }
}
