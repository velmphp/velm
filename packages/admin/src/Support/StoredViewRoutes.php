<?php

declare(strict_types=1);

namespace Velm\Admin\Support;

use Velm\Admin\Pages\StoredViewCreatePage;
use Velm\Admin\Pages\StoredViewEditPage;
use Velm\Admin\Pages\StoredViewListPage;
use Velm\Admin\Pages\StoredViewRecordPage;
use Velm\Environment;
use Velm\Views\ViewRegistry;

final class StoredViewRoutes
{
    /**
     * @return array{module: string, view: string}|null
     */
    public static function parseListHref(?string $href): ?array
    {
        if ($href === null || $href === '') {
            return null;
        }

        if (preg_match('#^/velm/views/([^/]+)/([^/]+)$#', $href, $matches) !== 1) {
            return null;
        }

        return [
            'module' => $matches[1],
            'view' => $matches[2],
        ];
    }

    public static function listHref(string $module, string $listView): string
    {
        return "/velm/views/{$module}/{$listView}";
    }

    public static function listViewFromFormView(string $formView): string
    {
        if (str_ends_with($formView, '.form')) {
            return substr($formView, 0, -strlen('.form')).'.list';
        }

        return $formView;
    }

    public static function listViewFromRecordView(string $recordView): string
    {
        if (str_ends_with($recordView, '.detail')) {
            return substr($recordView, 0, -strlen('.detail')).'.list';
        }

        return self::listViewFromFormView($recordView);
    }

    /**
     * @return array{module: string, view: string}|null
     */
    public static function parseViewHref(?string $href): ?array
    {
        return self::parseListHref($href);
    }

    public static function viewHref(string $module, string $viewName): string
    {
        return self::listHref($module, $viewName);
    }

    public static function viewPageUrl(string $module, string $viewName): string
    {
        return StoredViewListPage::getUrl([
            'module' => $module,
            'viewName' => $viewName,
        ], panel: 'velm');
    }

    public static function presentationType(string $module, string $viewName): string
    {
        $arch = app(ViewRegistry::class)->arch(app(Environment::class), $module, $viewName);
        $type = $arch['view_type'] ?? null;

        return is_string($type) && $type !== '' ? $type : 'list';
    }

    public static function siblingListView(string $module, string $viewName): string
    {
        try {
            $arch = app(ViewRegistry::class)->arch(app(Environment::class), $module, $viewName);
            $listView = $arch['list_view'] ?? null;

            if (is_string($listView) && $listView !== '') {
                return $listView;
            }
        } catch (\Throwable) {
            // Fall back to naming convention when the view is not synced yet.
        }

        if (preg_match('/^(.+)\.[^.]+$/', $viewName, $matches) === 1) {
            return $matches[1].'.list';
        }

        return self::listViewFromFormView($viewName);
    }

    public static function listPageUrl(string $module, string $viewName): string
    {
        return self::viewPageUrl($module, $viewName);
    }

    public static function createPageUrl(string $module, string $formView): string
    {
        return StoredViewCreatePage::getUrl([
            'module' => $module,
            'viewName' => $formView,
        ], panel: 'velm');
    }

    public static function editPageUrl(string $module, string $formView, int $recordId): string
    {
        return StoredViewEditPage::getUrl([
            'module' => $module,
            'viewName' => $formView,
            'record' => $recordId,
        ], panel: 'velm');
    }

    public static function recordPageUrl(string $module, string $viewName, int $recordId): string
    {
        $recordView = self::recordViewFromFormView($module, $viewName);

        return StoredViewRecordPage::getUrl([
            'module' => $module,
            'viewName' => $recordView,
            'record' => $recordId,
        ], panel: 'velm');
    }

    public static function recordViewFromFormView(string $module, string $viewName): string
    {
        if (str_ends_with($viewName, '.detail')) {
            return $viewName;
        }

        if (str_ends_with($viewName, '.form')) {
            $detailView = substr($viewName, 0, -strlen('.form')).'.detail';
            $env = app(Environment::class);

            if ($env->registry->has('ir.ui.view')) {
                $exists = $env->model('ir.ui.view')->search([
                    ['module', '=', $module],
                    ['name', '=', $detailView],
                ])->count() > 0;

                if ($exists) {
                    return $detailView;
                }
            }
        }

        return $viewName;
    }
}
