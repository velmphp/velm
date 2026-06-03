<?php

declare(strict_types=1);

namespace Velm\Admin\Support;

use Illuminate\Http\Request;
use Velm\Admin\Pages\FileLibraryPage;
use Velm\Admin\Pages\FilePropertiesPage;
use Velm\Admin\Pages\StoredViewCreatePage;
use Velm\Admin\Pages\StoredViewEditPage;
use Velm\Admin\Pages\StoredViewListPage;
use Velm\Admin\Pages\StoredViewRecordPage;

final class MenuActivePath
{
    public static function forRequest(Request $request): ?string
    {
        $storedView = self::storedViewHrefFromRequest($request);

        if ($storedView !== null) {
            return $storedView;
        }

        $pageClass = self::pageClassFromRequest($request);

        if ($pageClass !== null) {
            $canonical = ArchPageMap::canonicalHrefForPage($pageClass);

            if ($canonical !== null) {
                return $canonical;
            }

            if ($pageClass === FileLibraryPage::class) {
                return '/web/files/library';
            }
        }

        return self::shellPathFromRequest($request);
    }

    /**
     * Non-Livewire routes that still use the Velm shell (file library JSON, etc.).
     */
    private static function shellPathFromRequest(Request $request): ?string
    {
        if ($request->is('web/files', 'web/files/*')) {
            $path = '/'.ltrim($request->path(), '/');

            return $path !== '/' ? $path : null;
        }

        return null;
    }

    private static function storedViewHrefFromRequest(Request $request): ?string
    {
        $class = self::livewireClassFromRequest($request);

        if ($class === null) {
            return null;
        }

        $module = $request->route()?->parameter('module');
        $viewName = $request->route()?->parameter('viewName');

        if (! is_string($module) || ! is_string($viewName) || $module === '' || $viewName === '') {
            return null;
        }

        if ($class === StoredViewListPage::class) {
            return StoredViewRoutes::listHref($module, $viewName);
        }

        if ($class === StoredViewRecordPage::class || $class === StoredViewCreatePage::class || $class === StoredViewEditPage::class) {
            return StoredViewRoutes::listHref(
                $module,
                StoredViewRoutes::listViewFromRecordView($viewName),
            );
        }

        return null;
    }

    /**
     * @return class-string|null
     */
    private static function pageClassFromRequest(Request $request): ?string
    {
        $class = self::livewireClassFromRequest($request);

        if ($class === null) {
            return null;
        }

        if (ArchPageMap::canonicalHrefForPage($class) !== null) {
            return $class;
        }

        if ($class === FileLibraryPage::class || $class === FilePropertiesPage::class) {
            return $class;
        }

        return null;
    }

    /**
     * @return class-string|null
     */
    private static function livewireClassFromRequest(Request $request): ?string
    {
        $route = $request->route();

        if ($route === null) {
            return null;
        }

        $component = $route->getAction('livewire_component');

        if (is_string($component) && $component !== '' && class_exists($component)) {
            return $component;
        }

        $uses = $route->getAction('uses');

        if (! is_string($uses) || $uses === '') {
            return null;
        }

        $class = str_contains($uses, '@') ? explode('@', $uses, 2)[0] : $uses;

        if (! is_string($class) || ! class_exists($class)) {
            return null;
        }

        return $class;
    }
}
