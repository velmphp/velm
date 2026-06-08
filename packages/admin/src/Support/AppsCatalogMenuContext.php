<?php

declare(strict_types=1);

namespace Velm\Admin\Support;

use Illuminate\Http\Request;
use Velm\Admin\Pages\AppsPage;
use Velm\Environment;
use Velm\Modules\AppsCatalog;
use Velm\Views\Menu\MenuLayout;
use Velm\Views\Menu\MenuTreeBuilder;

final class AppsCatalogMenuContext
{
    public static function matches(Request $request): bool
    {
        $panel = trim((string) config('velm.panel_path', VelmPanel::path()), '/');

        return $request->is($panel.'/apps', $panel.'/apps/*');
    }

    /**
     * @return array<string, mixed>
     */
    public static function build(Request $request, Environment $env): array
    {
        $catalog = (new AppsCatalog)->entries(self::addonPaths());
        $summary = self::summarize($catalog);
        $categories = self::categories($catalog);
        $activeModule = $request->route('name');
        $activeModule = is_string($activeModule) && $activeModule !== '' ? $activeModule : null;

        return [
            'menu_layout' => MenuLayout::APPS_CATALOG,
            'menu' => [],
            'apps_summary' => $summary,
            'apps_states' => [
                ['key' => '', 'label' => 'All', 'count' => $summary['total']],
                ['key' => 'installed', 'label' => 'Installed', 'count' => $summary['installed']],
                ['key' => 'to_upgrade', 'label' => 'Upgrade', 'count' => $summary['to_upgrade']],
                ['key' => 'needs_sync', 'label' => 'Sync pending', 'count' => $summary['needs_sync']],
                ['key' => 'uninstalled', 'label' => 'Not installed', 'count' => $summary['uninstalled']],
            ],
            'apps_categories' => $categories,
            'apps_active_module' => $activeModule,
            'apps_catalog_url' => AppsPage::getUrl(),
            'workspace_roots' => self::workspaceRoots($env),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $catalog
     * @return array{total: int, installed: int, to_upgrade: int, needs_sync: int, uninstalled: int}
     */
    private static function summarize(array $catalog): array
    {
        return [
            'total' => count($catalog),
            'installed' => count(array_filter($catalog, static fn (array $c): bool => ($c['state'] ?? '') === 'installed')),
            'to_upgrade' => count(array_filter($catalog, static fn (array $c): bool => ($c['state'] ?? '') === 'to_upgrade')),
            'needs_sync' => count(array_filter($catalog, static fn (array $c): bool => ($c['state'] ?? '') === 'needs_sync')),
            'uninstalled' => count(array_filter($catalog, static fn (array $c): bool => ($c['state'] ?? '') === 'uninstalled')),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $catalog
     * @return list<string>
     */
    private static function categories(array $catalog): array
    {
        $categories = [];

        foreach ($catalog as $entry) {
            $cat = (string) ($entry['category'] ?? '');

            if ($cat !== '') {
                $categories[$cat] = true;
            }
        }

        $sorted = array_keys($categories);
        sort($sorted, SORT_NATURAL | SORT_FLAG_CASE);

        return $sorted;
    }

    /**
     * @return list<array{label: string, href: string|null, icon: string|null}>
     */
    private static function workspaceRoots(Environment $env): array
    {
        $tree = app(MenuTreeBuilder::class)->build($env, null);
        $roots = [];

        foreach ($tree as $node) {
            $href = MenuTreeBuilder::entryHref($node, $env);
            $label = (string) ($node['label'] ?? '');

            if ($label === '' || $href === null) {
                continue;
            }

            $roots[] = [
                'label' => $label,
                'href' => $href,
                'icon' => isset($node['icon']) && is_string($node['icon']) ? $node['icon'] : null,
            ];
        }

        return $roots;
    }

    /**
     * @return list<string>
     */
    private static function addonPaths(): array
    {
        /** @var list<string> $roots */
        $roots = config('velm.addon_paths', []);

        return $roots;
    }
}
