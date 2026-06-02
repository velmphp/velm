<?php

declare(strict_types=1);

namespace Velm\Framework\Console;

use Illuminate\Console\Command;
use Velm\Console\Scaffold\ModulePathResolver;
use Velm\Console\Scaffold\ScaffoldRegistryLoader;
use Velm\Console\Scaffold\ViewScaffolder;

final class MakeViewCommand extends Command
{
    protected $signature = 'velm:make:view
                            {model : Model technical or short name (e.g. res.partner or product)}
                            {--module= : Owning module (inferred from model or cwd when omitted)}
                            {--path= : Addon root to search}
                            {--minimal : Stub with name field only (skip model introspection)}
                            {--force : Overwrite an existing views file}';

    protected $description = 'Scaffold list and form views for a model';

    public function handle(ViewScaffolder $scaffolder, ScaffoldRegistryLoader $registryLoader): int
    {
        $modelInput = (string) $this->argument('model');
        $fromModel = ! (bool) $this->option('minimal');
        $addonRoot = $this->option('path');
        $addonRoot = is_string($addonRoot) && $addonRoot !== '' ? $addonRoot : null;

        $moduleName = $this->option('module');

        if (! is_string($moduleName) || $moduleName === '') {
            $moduleName = $registryLoader->inferModuleForModel($modelInput, $addonRoot)
                ?? ModulePathResolver::inferModuleFromCwd();
        }

        if (! is_string($moduleName) || $moduleName === '') {
            $this->components->error(
                'Pass --module=<name>, a registered technical model name, or run from inside a module directory.',
            );

            return self::FAILURE;
        }

        $moduleName = strtolower($moduleName);

        try {
            $modulePath = ModulePathResolver::findModulePath($moduleName, $addonRoot);
            $registry = $fromModel
                ? $registryLoader->loadForModule($moduleName, $addonRoot)
                : null;

            $result = $scaffolder->scaffold(
                $modelInput,
                $moduleName,
                $modulePath,
                $fromModel,
                (bool) $this->option('force'),
                $registry,
            );
        } catch (\Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $mode = $fromModel ? 'from model fields' : 'minimal stub';
        $this->components->info("Created {$result['path']} ({$mode})");
        $this->line("  Views: {$result['viewStem']}.list, {$result['viewStem']}.form");
        $this->line('  php artisan velm:make:menu (when available)');
        $this->line("  php artisan velm:module:sync --module={$moduleName}");

        return self::SUCCESS;
    }
}
