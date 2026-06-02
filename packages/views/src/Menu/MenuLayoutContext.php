<?php

declare(strict_types=1);

namespace Velm\Views\Menu;

final class MenuLayoutContext
{
    /**
     * @param  list<array<string, mixed>>  $menuTree
     * @return array<string, mixed>
     */
    public static function forTree(
        array $menuTree,
        ?string $currentPath,
        ?string $layout = null,
    ): array {
        $mode = MenuLayout::resolve($layout);

        $context = [
            'menu' => $menuTree,
            'menu_layout' => $mode,
        ];

        if ($mode !== MenuLayout::APPS) {
            return $context;
        }

        [$root, $rootIndex] = MenuTreeBuilder::activeRoot($menuTree, $currentPath);

        $context['menu_roots'] = array_map(
            static function (array $node, int $index): array {
                return [
                    ...$node,
                    'nav_href' => MenuTreeBuilder::entryHref($node),
                    'root_index' => $index,
                ];
            },
            $menuTree,
            array_keys($menuTree),
        );
        $context['menu_active_root'] = $root;
        $context['menu_active_root_index'] = $rootIndex;
        $context['menu_secondary'] = $root !== null ? ($root['children'] ?? []) : [];

        return $context;
    }
}
