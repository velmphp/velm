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

    public function purgeModule(string $module, Environment $env): void
    {
        $this->syncMenus($module, [], $env);
    }

    /**
     * @param  list<array<string, mixed>>  $menus
     */
    public function syncMenus(string $module, array $menus, Environment $env): void
    {
        if (! $env->registry->has('ir.ui.menu')) {
            return;
        }

        if ($menus === []) {
            $this->pruneStaleMenus($module, [], $env);

            return;
        }

        $Menu = $env->model('ir.ui.menu');

        $syncedNames = [];

        foreach ($this->orderMenus($menus, $module) as $menu) {
            $syncedNames[] = (string) $menu['name'];
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

        $this->pruneStaleMenus($module, $syncedNames, $env);
    }

    /**
     * @param  list<string>  $syncedNames
     */
    private function pruneStaleMenus(string $module, array $syncedNames, Environment $env): void
    {
        $Menu = $env->model('ir.ui.menu');
        $declared = array_flip($syncedNames);

        /** @var array<int, array<string, mixed>> $stale */
        $stale = [];

        foreach ($Menu->search([['module', '=', $module]])->read() as $row) {
            $name = (string) ($row['name'] ?? '');

            if ($name === '' || isset($declared[$name])) {
                continue;
            }

            $stale[(int) $row['id']] = $row;
        }

        while ($stale !== []) {
            $removed = false;

            foreach ($stale as $id => $row) {
                $hasStaleChild = false;

                foreach ($stale as $other) {
                    if ((int) ($other['parent_id'] ?? 0) === $id) {
                        $hasStaleChild = true;

                        break;
                    }
                }

                if ($hasStaleChild) {
                    continue;
                }

                $env->browse('ir.ui.menu', [$id])->unlink();
                unset($stale[$id]);
                $removed = true;
            }

            if (! $removed) {
                break;
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
