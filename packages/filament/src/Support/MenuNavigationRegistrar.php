<?php

declare(strict_types=1);

namespace Velm\Filament\Support;

use Filament\Facades\Filament;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Velm\Environment;
use Velm\Views\MenuRegistry;

final class MenuNavigationRegistrar
{
    public function __construct(
        private readonly MenuRegistry $menus = new MenuRegistry,
    ) {}

    public function register(Panel $panel): void
    {
        if (! app()->bound(Environment::class)) {
            return;
        }

        $env = app(Environment::class);
        $groups = [];
        $items = [];

        foreach ($this->menus->tree($env) as $root) {
            $this->collectNavigation($root, null, $groups, $items);
        }

        if ($groups !== []) {
            $panel->navigationGroups($groups);
        }

        if ($items !== []) {
            Filament::registerNavigationItems($items);
        }
    }

    /**
     * @param  array{menu: array<string, mixed>, children: list<mixed>}  $node
     * @param  array<string, NavigationGroup>  $groups
     * @param  list<NavigationItem>  $items
     */
    private function collectNavigation(
        array $node,
        ?string $groupLabel,
        array &$groups,
        array &$items,
    ): void {
        $menu = $node['menu'];
        $label = (string) $menu['label'];
        $children = $node['children'];

        if ($children !== []) {
            if ($groupLabel === null) {
                $groups[$label] = NavigationGroup::make()->label($label);
            }

            foreach ($children as $child) {
                $this->collectNavigation($child, $groupLabel ?? $label, $groups, $items);
            }

            return;
        }

        $pageClass = ArchPageMap::pageClassForHref($menu['href'] ?? null);

        if ($pageClass === null) {
            return;
        }

        $item = NavigationItem::make($label)
            ->url($pageClass::getUrl(panel: 'velm'))
            ->sort((int) ($menu['sequence'] ?? 10));

        if ($groupLabel !== null) {
            $item->group($groupLabel);
        }

        if (! empty($menu['icon'])) {
            $icon = (string) $menu['icon'];

            if (! str_contains($icon, 'heroicon-')) {
                $icon = 'heroicon-o-'.$icon;
            }

            $item->icon($icon);
        }

        $items[] = $item;
    }
}
