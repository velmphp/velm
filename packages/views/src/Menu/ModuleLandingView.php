<?php

declare(strict_types=1);

namespace Velm\Views\Menu;

use Velm\Environment;

final class ModuleLandingView
{
    public static function storedViewHref(string $module, string $viewName): string
    {
        return "/velm/views/{$module}/{$viewName}";
    }

    public static function moduleFromStoredViewHref(?string $href): ?string
    {
        if ($href === null || preg_match('#^/velm/views/([^/]+)/#', $href, $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }

    /**
     * @param  array<string, mixed>  $node
     * @return list<string>
     */
    public static function modulesInNode(array $node): array
    {
        $modules = [];

        $module = self::moduleFromStoredViewHref($node['href'] ?? null);

        if ($module !== null) {
            $modules[$module] = true;
        }

        foreach ($node['children'] ?? [] as $child) {
            if (! is_array($child)) {
                continue;
            }

            foreach (self::modulesInNode($child) as $childModule) {
                $modules[$childModule] = true;
            }
        }

        return array_keys($modules);
    }

    public static function dashboardHref(Environment $env, string $module): ?string
    {
        if (! $env->registry->has('ir.ui.view')) {
            return null;
        }

        $views = $env->model('ir.ui.view')->search([
            ['module', '=', $module],
            ['view_type', '=', 'dashboard'],
        ], limit: 1, order: '"name" ASC')->read(['name']);

        if ($views === []) {
            return null;
        }

        $name = (string) ($views[0]['name'] ?? '');

        return $name !== '' ? self::storedViewHref($module, $name) : null;
    }
}
