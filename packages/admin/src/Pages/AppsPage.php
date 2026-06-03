<?php

declare(strict_types=1);

namespace Velm\Admin\Pages;

use Illuminate\Contracts\Support\Htmlable;
use Velm\Framework\VelmManager;
use Velm\Admin\Support\VelmNotify;
use Velm\Modules\AppsCatalog;

final class AppsPage extends VelmShellPage
{
    protected static ?string $slug = 'apps';

    public function getTitle(): string|Htmlable
    {
        return 'Apps';
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function moduleCatalog(): array
    {
        return $this->catalog()->entries($this->addonPaths());
    }

    /**
     * @return list<string>
     */
    public function catalogCategories(): array
    {
        $categories = [];

        foreach ($this->moduleCatalog() as $entry) {
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
     * @return array{total: int, installed: int, to_upgrade: int, needs_sync: int, uninstalled: int}
     */
    public function catalogSummary(): array
    {
        $catalog = $this->moduleCatalog();

        return [
            'total' => count($catalog),
            'installed' => count(array_filter($catalog, static fn (array $c): bool => ($c['state'] ?? '') === 'installed')),
            'to_upgrade' => count(array_filter($catalog, static fn (array $c): bool => ($c['state'] ?? '') === 'to_upgrade')),
            'needs_sync' => count(array_filter($catalog, static fn (array $c): bool => ($c['state'] ?? '') === 'needs_sync')),
            'uninstalled' => count(array_filter($catalog, static fn (array $c): bool => ($c['state'] ?? '') === 'uninstalled')),
        ];
    }

    public function installModule(string $name): void
    {
        $this->guardSuperuser();
        $entry = $this->catalog()->entry($this->addonPaths(), $name);

        if ($entry === null) {
            VelmNotify::flash('error', "Unknown module {$name}.");

            return;
        }

        if (($entry['deps_unknown'] ?? []) !== []) {
            VelmNotify::flash('error', 'Missing dependencies on disk: '.implode(', ', $entry['deps_unknown']));

            return;
        }

        try {
            app(VelmManager::class)->install($name);
            VelmNotify::flash('success', "Installed {$name}");
        } catch (\Throwable $e) {
            VelmNotify::flash('error', $e->getMessage());
        }
    }

    public function syncModule(string $name): void
    {
        $this->guardSuperuser();

        try {
            app(VelmManager::class)->sync($name);
            VelmNotify::flash('success', "Synced {$name}");
        } catch (\Throwable $e) {
            VelmNotify::flash('error', $e->getMessage());
        }
    }

    public function upgradeModule(string $name): void
    {
        $this->guardSuperuser();

        try {
            app(VelmManager::class)->upgrade($name);
            VelmNotify::flash('success', "Updated {$name}");
        } catch (\Throwable $e) {
            VelmNotify::flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        return view('velm-ui::pages.apps');
    }

    private function catalog(): AppsCatalog
    {
        return new AppsCatalog;
    }

    /**
     * @return list<string>
     */
    private function addonPaths(): array
    {
        /** @var list<string> $roots */
        $roots = config('velm.addon_paths', []);

        return $roots;
    }

    private function guardSuperuser(): void
    {
        $env = app(\Velm\Environment::class);

        if (! $env->isSuperuser()) {
            abort(403, 'Module management requires superuser.');
        }
    }
}
