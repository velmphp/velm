<?php

declare(strict_types=1);

namespace Velm\Modules;

final class AppsCatalog
{
    /**
     * @param  list<string>|null  $protectedModules  Bootstrap modules that cannot be uninstalled; null reads config.
     */
    public function __construct(
        private readonly ModuleDiscovery $discovery = new ModuleDiscovery,
        private readonly DependencyResolver $resolver = new DependencyResolver,
        private readonly ModuleRepository $repository = new ModuleRepository,
        private readonly ModuleInstaller $installer = new ModuleInstaller,
        private readonly ?array $protectedModules = null,
    ) {}

    /**
     * @param  list<string>  $roots
     * @return list<array<string, mixed>>
     */
    public function entries(array $roots): array
    {
        $specs = $this->discovery->discover($roots);
        $installed = $this->installedVersions();
        $catalog = [];

        foreach ($this->resolver->resolve($specs) as $spec) {
            $name = $spec->name;
            $inst = $installed[$name] ?? null;
            $versionUpgrade = false;
            $pendingMigrations = false;
            $hasSchemaDiff = false;
            $schemaDiffSummary = '';
            $hasSchemaDrift = false;
            $schemaDriftSummary = '';
            $hasUiSync = false;
            $uiSyncSummary = '';

            if ($inst === null) {
                $state = 'uninstalled';
            } else {
                $installedVersion = ModuleVersion::parse($inst);
                $versionUpgrade = ModuleVersion::needsUpgrade($installedVersion, $spec->version);
                $pendingMigrations = $versionUpgrade;

                if (! $versionUpgrade) {
                    try {
                        $diff = $this->installer->diff($name, $roots);
                        $canAlter = $this->installer->canAlterColumnNullability();
                        $hasSchemaDiff = $diff->isSyncActionable($canAlter);
                        $hasSchemaDrift = $diff->hasDrift() && ! $hasSchemaDiff;
                        $schemaDiffSummary = $hasSchemaDiff
                            ? $this->summarizeActionableDiff($diff, $canAlter)
                            : '';
                        $schemaDriftSummary = $hasSchemaDrift
                            ? $this->summarizeDriftDiff($diff, $canAlter)
                            : '';
                    } catch (\Throwable) {
                        $hasSchemaDiff = false;
                        $hasSchemaDrift = false;
                    }

                    try {
                        $uiDiff = $this->installer->uiSyncDiff($name, $roots);
                        $hasUiSync = $uiDiff->hasChanges();
                        $uiSyncSummary = $hasUiSync ? $uiDiff->summary() : '';
                    } catch (\Throwable) {
                        $hasUiSync = false;
                    }
                }

                $state = match (true) {
                    $versionUpgrade => 'to_upgrade',
                    $hasSchemaDiff, $hasUiSync => 'needs_sync',
                    default => 'installed',
                };
            }

            $depsMissing = [];
            foreach ($spec->depends as $dep) {
                if (! isset($installed[$dep])) {
                    $depsMissing[] = $dep;
                }
            }

            $depsUnknown = array_values(array_filter(
                $depsMissing,
                static fn (string $dep): bool => ! isset($specs[$dep]),
            ));

            $uninstallPreview = null;

            if ($inst !== null) {
                try {
                    $uninstallPreview = $this->installer->uninstallPreview(
                        $name,
                        $roots,
                        $this->protectedModules(),
                    );
                } catch (\Throwable) {
                    $uninstallPreview = null;
                }
            }

            $catalog[] = [
                'name' => $name,
                'display_name' => $spec->displayName(),
                'summary' => $spec->summary,
                'description' => $spec->description,
                'category' => $spec->category !== '' ? $spec->category : 'Uncategorised',
                'author' => $spec->author,
                'icon' => $spec->icon,
                'available_version' => $spec->versionString(),
                'installed_version' => $inst,
                'state' => $state,
                'can_uninstall' => $uninstallPreview?->canUninstall ?? false,
                'uninstall_blockers' => $uninstallPreview?->blockers() ?? [],
                'version_upgrade' => $versionUpgrade,
                'pending_migrations' => $pendingMigrations,
                'has_schema_diff' => $hasSchemaDiff,
                'schema_diff_summary' => $this->combineSyncSummaries($schemaDiffSummary, $uiSyncSummary),
                'has_ui_sync' => $hasUiSync,
                'ui_sync_summary' => $uiSyncSummary,
                'has_schema_drift' => $hasSchemaDrift,
                'schema_drift_summary' => $schemaDriftSummary,
                'depends' => $spec->depends,
                'deps_missing' => $depsMissing,
                'deps_unknown' => $depsUnknown,
            ];
        }

        usort($catalog, static fn (array $a, array $b): int => strcasecmp(
            (string) $a['display_name'],
            (string) $b['display_name'],
        ) ?: strcmp((string) $a['name'], (string) $b['name']));

        return $catalog;
    }

    /**
     * @param  list<string>  $roots
     * @return array<string, mixed>|null
     */
    public function entry(array $roots, string $name): ?array
    {
        foreach ($this->entries($roots) as $entry) {
            if ($entry['name'] === $name) {
                return $entry;
            }
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    private function installedVersions(): array
    {
        $map = [];

        foreach ($this->repository->installedNames() as $name) {
            $version = $this->repository->installedVersion($name);

            if ($version !== null) {
                $map[$name] = $version;
            }
        }

        return $map;
    }

    private function summarizeActionableDiff(\Velm\Schema\SchemaDiff $diff, bool $canAlterColumnNullability): string
    {
        $parts = [];

        if ($diff->newTables !== []) {
            $parts[] = count($diff->newTables).' new table(s)';
        }

        if ($diff->newColumns !== []) {
            $parts[] = count($diff->newColumns).' new column(s)';
        }

        if ($canAlterColumnNullability && $diff->alterations !== []) {
            $parts[] = count($diff->alterations).' alteration(s)';
        }

        return $parts === [] ? 'Schema changes pending' : implode(', ', $parts);
    }

    private function combineSyncSummaries(string $schemaSummary, string $uiSummary): string
    {
        return trim(implode('; ', array_filter([$schemaSummary, $uiSummary], static fn (string $s): bool => $s !== '')));
    }

    private function summarizeDriftDiff(\Velm\Schema\SchemaDiff $diff, bool $canAlterColumnNullability): string
    {
        $parts = [];

        if ($diff->orphanColumns !== []) {
            $parts[] = count($diff->orphanColumns).' orphan column(s)';
        }

        if (! $canAlterColumnNullability && $diff->alterations !== []) {
            $parts[] = count($diff->alterations).' unsupported alteration(s)';
        }

        return $parts === [] ? 'Schema drift (Sync cannot auto-fix)' : implode(', ', $parts);
    }

    /**
     * @return list<string>
     */
    private function protectedModules(): array
    {
        if ($this->protectedModules !== null) {
            return $this->protectedModules;
        }

        if (function_exists('config')) {
            /** @var mixed $modules */
            $modules = config('velm.bootstrap_modules');

            if (is_array($modules) && $modules !== []) {
                return array_values(array_map(static fn (mixed $name): string => (string) $name, $modules));
            }
        }

        return ['base', 'admin'];
    }
}
