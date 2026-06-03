<?php

declare(strict_types=1);

namespace Velm\Views\Sync;

use Velm\Environment;
use Velm\Modules\ModuleSpec;
use Velm\Views\Arch\ArchNormalizer;
use Velm\Views\Data\DataFileLoader;

final class UiSyncDiffer
{
    public function __construct(
        private readonly DataFileLoader $dataLoader = new DataFileLoader,
    ) {}

    public function diff(ModuleSpec $spec, Environment $env): UiSyncDiff
    {
        $result = new UiSyncDiff;

        if ($spec->data === []) {
            return $result;
        }

        $loaded = $this->dataLoader->load($spec);

        if ($env->registry->has('ir.ui.view')) {
            $this->diffViews($spec, $env, $loaded, $result);
        }

        if ($env->registry->has('ir.ui.menu')) {
            $this->diffMenus($spec, $env, $loaded['menus'], $result);
        }

        return $result;
    }

    public function hasPending(ModuleSpec $spec, Environment $env): bool
    {
        return $this->diff($spec, $env)->hasChanges();
    }

    /**
     * @param  array{views: list<array<string, mixed>>, view_inherits: list<array<string, mixed>>, menus: list<array<string, mixed>>}  $loaded
     */
    private function diffViews(ModuleSpec $spec, Environment $env, array $loaded, UiSyncDiff $result): void
    {
        $View = $env->model('ir.ui.view');
        $expected = [];

        foreach ($loaded['views'] as $view) {
            $name = (string) ($view['name'] ?? '');

            if ($name === '') {
                continue;
            }

            $expected[$name] = $this->fingerprintBaseView($view);
        }

        foreach ($loaded['view_inherits'] as $inherit) {
            $name = (string) ($inherit['name'] ?? '');

            if ($name === '') {
                continue;
            }

            $expected[$name] = $this->fingerprintInheritView($spec, $env, $inherit);
        }

        $rows = $View->search([['module', '=', $spec->name]])->read([
            'name', 'model', 'view_type', 'arch', 'priority', 'inherit_id', 'operations',
        ]);

        $actual = [];

        foreach ($rows as $row) {
            $name = (string) ($row['name'] ?? '');

            if ($name === '') {
                continue;
            }

            $actual[$name] = $this->fingerprintStoredView($row);
        }

        foreach ($expected as $name => $fingerprint) {
            if (! isset($actual[$name])) {
                $result->newViews[] = $name;

                continue;
            }

            if ($actual[$name] !== $fingerprint) {
                $result->changedViews[] = $name;
            }
        }

        foreach (array_keys($actual) as $name) {
            if (! isset($expected[$name])) {
                $result->removedViews[] = $name;
            }
        }
    }

    /**
     * @param  list<array<string, mixed>>  $menus
     */
    private function diffMenus(ModuleSpec $spec, Environment $env, array $menus, UiSyncDiff $result): void
    {
        $Menu = $env->model('ir.ui.menu');
        $expected = [];

        foreach ($this->orderMenus($menus, $spec->name) as $menu) {
            $name = (string) ($menu['name'] ?? '');

            if ($name === '') {
                continue;
            }

            $expected[$name] = $this->fingerprintDiskMenu($menu, $spec->name, $env);
        }

        $rows = $Menu->search([['module', '=', $spec->name]])->read([
            'name', 'label', 'parent_id', 'sequence', 'href', 'icon', 'active',
        ]);

        $actual = [];

        foreach ($rows as $row) {
            $name = (string) ($row['name'] ?? '');

            if ($name === '') {
                continue;
            }

            $actual[$name] = $this->fingerprintStoredMenu($row);
        }

        foreach ($expected as $name => $fingerprint) {
            if (! isset($actual[$name])) {
                $result->newMenus[] = $name;

                continue;
            }

            if ($actual[$name] !== $fingerprint) {
                $result->changedMenus[] = $name;
            }
        }

        foreach (array_keys($actual) as $name) {
            if (! isset($expected[$name])) {
                $result->removedMenus[] = $name;
            }
        }
    }

    /**
     * @param  array<string, mixed>  $view
     * @return array<string, mixed>
     */
    private function fingerprintBaseView(array $view): array
    {
        $arch = $view['arch'];
        $archObject = is_string($arch) ? json_decode($arch, true, flags: JSON_THROW_ON_ERROR) : $arch;

        if (! is_array($archObject)) {
            throw new \InvalidArgumentException('View arch must be an array or JSON string.');
        }

        return [
            'kind' => 'base',
            'model' => (string) $view['model'],
            'view_type' => (string) $view['view_type'],
            'arch' => json_encode(
                ArchNormalizer::normalize($archObject, (string) $view['view_type']),
                JSON_THROW_ON_ERROR,
            ),
            'priority' => (int) ($view['priority'] ?? 16),
        ];
    }

    /**
     * @param  array<string, mixed>  $inherit
     * @return array<string, mixed>
     */
    private function fingerprintInheritView(ModuleSpec $spec, Environment $env, array $inherit): array
    {
        [$parentModule, $parentName] = $this->parseInheritRef((string) $inherit['inherit']);
        $parent = $env->model('ir.ui.view')->search([
            ['module', '=', $parentModule],
            ['name', '=', $parentName],
        ]);

        $parentRow = $parent->count() > 0 ? $parent->read()[0] : null;

        $operations = $inherit['operations'];
        $operationsJson = is_string($operations)
            ? $operations
            : json_encode($operations, JSON_THROW_ON_ERROR);

        return [
            'kind' => 'inherit',
            'inherit_id' => $parent->count() > 0 ? $parent->ids()[0] : null,
            'model' => $parentRow !== null ? (string) $parentRow['model'] : '',
            'view_type' => $parentRow !== null ? (string) $parentRow['view_type'] : '',
            'operations' => $operationsJson,
            'priority' => (int) ($inherit['priority'] ?? 16),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function fingerprintStoredView(array $row): array
    {
        if ($row['inherit_id'] !== null && $row['inherit_id'] !== '') {
            return [
                'kind' => 'inherit',
                'inherit_id' => (int) $row['inherit_id'],
                'model' => (string) ($row['model'] ?? ''),
                'view_type' => (string) ($row['view_type'] ?? ''),
                'operations' => (string) ($row['operations'] ?? ''),
                'priority' => (int) ($row['priority'] ?? 16),
            ];
        }

        $arch = $row['arch'] ?? '';
        $archObject = is_string($arch) && $arch !== ''
            ? json_decode($arch, true, flags: JSON_THROW_ON_ERROR)
            : (is_array($arch) ? $arch : []);

        if (! is_array($archObject)) {
            $archObject = [];
        }

        $viewType = (string) ($row['view_type'] ?? 'form');

        return [
            'kind' => 'base',
            'model' => (string) ($row['model'] ?? ''),
            'view_type' => $viewType,
            'arch' => json_encode(ArchNormalizer::normalize($archObject, $viewType), JSON_THROW_ON_ERROR),
            'priority' => (int) ($row['priority'] ?? 16),
        ];
    }

    /**
     * @param  array<string, mixed>  $menu
     * @return array<string, mixed>
     */
    private function fingerprintDiskMenu(array $menu, string $module, Environment $env): array
    {
        $parentRef = $menu['parent'] ?? null;
        $parentId = null;

        if ($parentRef !== null && $parentRef !== '' && str_contains((string) $parentRef, '.')) {
            [$parentModule, $parentName] = explode('.', (string) $parentRef, 2);
            $parent = $env->model('ir.ui.menu')->search([
                ['module', '=', $parentModule],
                ['name', '=', $parentName],
            ]);
            $parentId = $parent->count() > 0 ? $parent->ids()[0] : null;
        }

        return $this->fingerprintMenuValues($menu, $parentId);
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function fingerprintStoredMenu(array $row): array
    {
        $parentId = $row['parent_id'] ?? null;

        return $this->fingerprintMenuValues($row, $parentId !== null && $parentId !== '' ? (int) $parentId : null);
    }

    /**
     * @param  array<string, mixed>  $menu
     * @return array<string, mixed>
     */
    private function fingerprintMenuValues(array $menu, ?int $parentId): array
    {
        return [
            'label' => (string) ($menu['label'] ?? ''),
            'parent_id' => $parentId,
            'sequence' => (int) ($menu['sequence'] ?? 10),
            'href' => $menu['href'] ?? null,
            'icon' => $menu['icon'] ?? null,
            'active' => filter_var($menu['active'] ?? true, FILTER_VALIDATE_BOOLEAN),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $menus
     * @return list<array<string, mixed>>
     */
    private function orderMenus(array $menus, string $module): array
    {
        $byName = [];

        foreach ($menus as $menu) {
            $byName[(string) ($menu['name'] ?? '')] = $menu;
        }

        $ordered = [];
        $visited = [];

        $visit = function (string $name) use (&$visit, &$ordered, &$visited, $byName, $module): void {
            if ($name === '' || isset($visited[$name])) {
                return;
            }

            $visited[$name] = true;
            $menu = $byName[$name] ?? null;

            if ($menu === null) {
                return;
            }

            $parentRef = $menu['parent'] ?? null;

            if (is_string($parentRef) && $parentRef !== '' && str_starts_with($parentRef, $module.'.')) {
                $visit(substr($parentRef, strlen($module) + 1));
            }

            $ordered[] = $menu;
        };

        foreach (array_keys($byName) as $name) {
            $visit($name);
        }

        return $ordered;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function parseInheritRef(string $inherit): array
    {
        if (! str_contains($inherit, '.')) {
            throw new \InvalidArgumentException("View inherit ref must be module.name, got {$inherit}.");
        }

        [$module, $name] = explode('.', $inherit, 2);

        return [$module, $name];
    }
}
