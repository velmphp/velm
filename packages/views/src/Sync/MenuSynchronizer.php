<?php

declare(strict_types=1);

namespace Velm\Views\Sync;

use Velm\Environment;
use Velm\Modules\ModuleSpec;
use Velm\Views\Data\DataFileLoader;

final class MenuSynchronizer
{
    public function __construct(
        private readonly DataFileLoader $dataLoader = new DataFileLoader,
    ) {}

    public function sync(ModuleSpec $spec, Environment $env): void
    {
        $menus = $this->dataLoader->load($spec)['menus'];
        $this->syncMenus($spec->name, $menus, $env);
    }

    /**
     * @param  list<array<string, mixed>>  $menus
     */
    public function syncMenus(string $module, array $menus, Environment $env): void
    {
        if ($menus === [] || ! $env->registry->has('ir.ui.menu')) {
            return;
        }

        $Menu = $env->model('ir.ui.menu');

        foreach ($this->orderMenus($menus, $module) as $menu) {
            $parentRef = $menu['parent'] ?? null;
            $parentId = null;

            if ($parentRef !== null && $parentRef !== '') {
                if (! str_contains((string) $parentRef, '.')) {
                    throw new \InvalidArgumentException(
                        "Menu parent {$parentRef} must be '<module>.<name>'.",
                    );
                }

                [$parentModule, $parentName] = explode('.', (string) $parentRef, 2);
                $parent = $Menu->search([
                    ['module', '=', $parentModule],
                    ['name', '=', $parentName],
                ]);

                if ($parent->count() === 0) {
                    throw new \RuntimeException(
                        "Menu {$module}.{$menu['name']} references parent {$parentRef} which is not installed.",
                    );
                }

                $parentId = $parent->ids()[0];
            }

            $existing = $Menu->search([
                ['module', '=', $module],
                ['name', '=', $menu['name']],
            ]);

            $values = [
                'module' => $module,
                'name' => $menu['name'],
                'label' => $menu['label'],
                'parent_id' => $parentId,
                'sequence' => $menu['sequence'] ?? 10,
                'href' => $menu['href'] ?? null,
                'icon' => $menu['icon'] ?? null,
                'active' => $menu['active'] ?? true,
            ];

            if ($existing->count() > 0) {
                $existing->write($values);
            } else {
                $Menu->create($values);
            }
        }
    }

    /**
     * @param  list<array<string, mixed>>  $menus
     * @return list<array<string, mixed>>
     */
    private function orderMenus(array $menus, string $module): array
    {
        /** @var array<string, array<string, mixed>> $byName */
        $byName = [];

        foreach ($menus as $menu) {
            $byName[(string) $menu['name']] = $menu;
        }

        $depthCache = [];

        $depth = function (array $entry) use (&$depth, &$depthCache, $byName, $module): int {
            $name = (string) $entry['name'];

            if (isset($depthCache[$name])) {
                return $depthCache[$name];
            }

            $parentRef = $entry['parent'] ?? null;

            if ($parentRef === null || $parentRef === '') {
                return $depthCache[$name] = 0;
            }

            if (! str_contains((string) $parentRef, '.')) {
                return $depthCache[$name] = 1;
            }

            [$parentModule, $parentName] = explode('.', (string) $parentRef, 2);

            if ($parentModule !== $module || ! isset($byName[$parentName])) {
                return $depthCache[$name] = 1;
            }

            return $depthCache[$name] = $depth($byName[$parentName]) + 1;
        };

        usort($menus, static function (array $a, array $b) use ($depth): int {
            $byDepth = $depth($a) <=> $depth($b);

            if ($byDepth !== 0) {
                return $byDepth;
            }

            $bySequence = ((int) ($a['sequence'] ?? 10)) <=> ((int) ($b['sequence'] ?? 10));

            if ($bySequence !== 0) {
                return $bySequence;
            }

            return strcmp((string) $a['label'], (string) $b['label']);
        });

        return $menus;
    }
}
