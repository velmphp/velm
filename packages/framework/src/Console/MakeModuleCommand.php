<?php

declare(strict_types=1);

namespace Velm\Framework\Console;

use Illuminate\Console\Command;
use Velm\Console\Scaffold\ModuleScaffolder;
use Velm\Console\Support\ModuleRoots;

final class MakeModuleCommand extends Command
{
    protected $signature = 'velm:make:module
                            {name : Technical module name (snake_case, e.g. inventory)}
                            {--path= : Addon root directory (default: first velm.addon_paths entry)}
                            {--depends=base : Comma-separated module dependencies}';

    protected $description = 'Scaffold a new Velm module under an addon root';

    public function handle(ModuleScaffolder $scaffolder): int
    {
        $name = strtolower((string) $this->argument('name'));
        $depends = $this->parseDepends((string) $this->option('depends'));
        $addonRoot = $this->resolveAddonRoot();

        try {
            $modulePath = $scaffolder->scaffold($name, $addonRoot, $depends);
        } catch (\Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Created module at {$modulePath}");
        $this->line('  __velm__.php');
        $this->line('  models/');
        $this->line('  migrations/');
        $this->components->warn('Next: add model classes, then php artisan velm:make:model (when available) or register models in __velm__.php.');
        $this->line("  php artisan velm:migrate --module={$name}");

        return self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function parseDepends(string $raw): array
    {
        $parts = array_values(array_filter(array_map('trim', explode(',', $raw))));

        return $parts !== [] ? $parts : ['base'];
    }

    private function resolveAddonRoot(): string
    {
        $explicit = $this->option('path');

        if (is_string($explicit) && $explicit !== '') {
            return $explicit;
        }

        if (function_exists('base_path')) {
            $appAddons = base_path('addons');

            if (is_string($appAddons) && $appAddons !== '') {
                return $appAddons;
            }
        }

        foreach (ModuleRoots::resolve() as $path) {
            if (! str_contains($path, DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR)) {
                return $path;
            }
        }

        return 'addons';
    }
}
