<?php

declare(strict_types=1);

namespace Velm\Views\Sync;

use Velm\Environment;
use Velm\Modules\ModuleSpec;
use Velm\Views\Arch\ArchNormalizer;
use Velm\Views\Data\DataFileLoader;

final class ViewSynchronizer
{
    public function __construct(
        private readonly DataFileLoader $dataLoader = new DataFileLoader,
    ) {}

    public function sync(ModuleSpec $spec, Environment $env): void
    {
        if (! $env->registry->has('ir.ui.view')) {
            return;
        }

        $loaded = $this->dataLoader->load($spec);

        $this->syncBaseViews($spec, $env, $loaded['views']);
        $this->syncViewInherits($spec, $env, $loaded['view_inherits']);

        $syncedNames = array_map(
            static fn (array $view): string => (string) $view['name'],
            [...$loaded['views'], ...$loaded['view_inherits']],
        );

        $this->pruneStaleViews($spec->name, $syncedNames, $env);
    }

    public function purgeModule(string $module, Environment $env): void
    {
        if (! $env->registry->has('ir.ui.view')) {
            return;
        }

        $this->pruneStaleViews($module, [], $env);
    }

    /**
     * @param  list<array<string, mixed>>  $views
     */
    private function syncBaseViews(ModuleSpec $spec, Environment $env, array $views): void
    {
        if ($views === []) {
            return;
        }

        $View = $env->model('ir.ui.view');

        foreach ($views as $view) {
            $required = ['name', 'model', 'view_type', 'arch'];
            $missing = array_diff($required, array_keys($view));

            if ($missing !== []) {
                throw new \InvalidArgumentException(
                    "Module {$spec->name}: view ".($view['name'] ?? '?').' missing keys: '.implode(', ', $missing),
                );
            }

            $arch = $view['arch'];
            $archObject = is_string($arch) ? json_decode($arch, true, flags: JSON_THROW_ON_ERROR) : $arch;

            if (! is_array($archObject)) {
                throw new \InvalidArgumentException(
                    "Module {$spec->name}: view {$view['name']} arch must be an array or JSON string.",
                );
            }

            $normalized = ArchNormalizer::normalize($archObject, (string) $view['view_type']);
            $archJson = json_encode($normalized, JSON_THROW_ON_ERROR);

            $existing = $View->search([
                ['module', '=', $spec->name],
                ['name', '=', $view['name']],
            ]);

            $values = [
                'module' => $spec->name,
                'name' => $view['name'],
                'model' => $view['model'],
                'view_type' => $view['view_type'],
                'arch' => $archJson,
                'priority' => $view['priority'] ?? 16,
                'inherit_id' => null,
                'operations' => null,
            ];

            if ($existing->count() > 0) {
                $existing->write($values);
            } else {
                $View->create($values);
            }
        }
    }

    /**
     * @param  list<array<string, mixed>>  $inherits
     */
    private function syncViewInherits(ModuleSpec $spec, Environment $env, array $inherits): void
    {
        if ($inherits === []) {
            return;
        }

        $View = $env->model('ir.ui.view');

        foreach ($inherits as $inherit) {
            $required = ['name', 'inherit', 'operations'];
            $missing = array_diff($required, array_keys($inherit));

            if ($missing !== []) {
                throw new \InvalidArgumentException(
                    "Module {$spec->name}: view inherit ".($inherit['name'] ?? '?').' missing keys: '.implode(', ', $missing),
                );
            }

            [$parentModule, $parentName] = $this->parseInheritRef((string) $inherit['inherit']);

            $parent = $View->search([
                ['module', '=', $parentModule],
                ['name', '=', $parentName],
            ]);

            if ($parent->count() === 0) {
                throw new \RuntimeException(
                    "Module {$spec->name}: parent view {$inherit['inherit']} not found.",
                );
            }

            $parentRow = $parent->read()[0];
            $operations = $inherit['operations'];
            $operationsJson = is_string($operations)
                ? $operations
                : json_encode($operations, JSON_THROW_ON_ERROR);

            $existing = $View->search([
                ['module', '=', $spec->name],
                ['name', '=', $inherit['name']],
            ]);

            $values = [
                'module' => $spec->name,
                'name' => $inherit['name'],
                'model' => $parentRow['model'],
                'view_type' => $parentRow['view_type'],
                'arch' => null,
                'priority' => $inherit['priority'] ?? 16,
                'inherit_id' => $parent->ids()[0],
                'operations' => $operationsJson,
            ];

            if ($existing->count() > 0) {
                $existing->write($values);
            } else {
                $View->create($values);
            }
        }
    }

    /**
     * @param  list<string>  $syncedNames
     */
    private function pruneStaleViews(string $module, array $syncedNames, Environment $env): void
    {
        $View = $env->model('ir.ui.view');
        $declared = array_flip($syncedNames);

        foreach ($View->search([['module', '=', $module]])->read() as $row) {
            $name = (string) ($row['name'] ?? '');

            if ($name === '' || isset($declared[$name])) {
                continue;
            }

            $env->browse('ir.ui.view', [(int) $row['id']])->unlink();
        }
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
