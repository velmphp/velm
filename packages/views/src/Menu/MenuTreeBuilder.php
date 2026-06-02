<?php

declare(strict_types=1);

namespace Velm\Views\Menu;

use Velm\Environment;
use Velm\Views\MenuRegistry;

final class MenuTreeBuilder
{
    public function __construct(
        private readonly MenuRegistry $registry = new MenuRegistry,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function build(Environment $env, ?string $currentPath = null): array
    {
        $tree = [];

        foreach ($this->registry->tree($env) as $root) {
            $tree[] = $this->toEntry($root);
        }

        return array_map(
            fn (array $item): array => $this->markActive($item, $currentPath),
            $tree,
        );
    }

    /**
     * @param  array{menu: array<string, mixed>, children: list<array<string, mixed>>}  $node
     * @return array<string, mixed>
     */
    private function toEntry(array $node): array
    {
        $menu = $node['menu'];

        return [
            'label' => (string) $menu['label'],
            'href' => $menu['href'] ?? null,
            'icon' => $menu['icon'] ?? null,
            'children' => array_map(
                fn (array $child): array => $this->toEntry($child),
                $node['children'],
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function markActive(array $item, ?string $currentPath): array
    {
        $normCurrent = self::normalizePath($currentPath);
        $normHref = self::normalizePath($item['href'] ?? null);

        $active = $normHref !== null
            && $normCurrent !== null
            && ($normCurrent === $normHref || str_starts_with($normCurrent, $normHref.'/'));

        foreach ($item['children'] as $index => $child) {
            $item['children'][$index] = $this->markActive($child, $currentPath);

            if ($item['children'][$index]['active'] ?? false) {
                $active = true;
            }
        }

        $item['active'] = $active;

        return $item;
    }

    public static function normalizePath(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        $base = explode('?', $path, 2)[0];
        $base = rtrim($base, '/');

        return $base !== '' ? $base : '/';
    }

    /**
     * @param  list<array<string, mixed>>  $menuTree
     */
    public static function activeRoot(array $menuTree, ?string $currentPath): array
    {
        if ($menuTree === []) {
            return [null, null];
        }

        if ($currentPath !== null) {
            foreach ($menuTree as $index => $root) {
                if (self::nodeMatchesPath($root, $currentPath)) {
                    return [$root, $index];
                }
            }

            foreach ($menuTree as $index => $root) {
                if ($root['active'] ?? false) {
                    return [$root, $index];
                }
            }
        }

        return [$menuTree[0], 0];
    }

    /**
     * @param  array<string, mixed>  $node
     */
    public static function nodeMatchesPath(array $node, string $currentPath): bool
    {
        $normCurrent = self::normalizePath($currentPath);
        $normHref = self::normalizePath($node['href'] ?? null);

        if ($normHref !== null && $normCurrent !== null
            && ($normCurrent === $normHref || str_starts_with($normCurrent, $normHref.'/'))) {
            return true;
        }

        foreach ($node['children'] ?? [] as $child) {
            if (self::nodeMatchesPath($child, $currentPath)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $node
     */
    public static function entryHref(array $node): ?string
    {
        $href = $node['href'] ?? null;

        if (is_string($href) && $href !== '') {
            return $href;
        }

        foreach ($node['children'] ?? [] as $child) {
            $childHref = self::entryHref($child);

            if ($childHref !== null) {
                return $childHref;
            }
        }

        return null;
    }
}
