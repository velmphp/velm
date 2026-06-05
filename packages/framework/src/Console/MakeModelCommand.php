<?php

declare(strict_types=1);

namespace Velm\Framework\Console;

use Illuminate\Console\Command;
use Velm\Console\Scaffold\ModelScaffolder;
use Velm\Console\Scaffold\ModulePathResolver;

final class MakeModelCommand extends Command
{
    protected $signature = 'velm:make:model
                            {model : Short or technical name (e.g. product or inventory.product)}
                            {--module= : Owning module (inferred when run inside a module directory)}
                            {--path= : Addon root to search (default: velm.addon_paths)}
                            {--force : Overwrite an existing model file}';

    protected $description = 'Scaffold a model class and register it in the module manifest';

    public function handle(ModelScaffolder $scaffolder): int
    {
        $modelInput = (string) $this->argument('model');
        $moduleName = $this->option('module');

        if (! is_string($moduleName) || $moduleName === '') {
            $moduleName = ModulePathResolver::inferModuleFromCwd();
        }

        if (! is_string($moduleName) || $moduleName === '') {
            $this->components->error(
                'Pass --module=<name> or run from inside <addon-root>/<module>/.',
            );

            return self::FAILURE;
        }

        $moduleName = strtolower($moduleName);
        $addonRoot = $this->option('path');
        $addonRoot = is_string($addonRoot) && $addonRoot !== '' ? $addonRoot : null;

        try {
            $modulePath = ModulePathResolver::findModulePath($moduleName, $addonRoot);
            $result = $scaffolder->scaffold(
                $modelInput,
                $moduleName,
                $modulePath,
                (bool) $this->option('force'),
            );
        } catch (\Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Created {$result['path']}");
        $this->line("  Model: {$result['technical']}");
        $this->line("  Class: {$result['namespace']}\\{$result['class']}");
        $this->line("  php artisan velm:make:view {$result['technical']} --module={$moduleName}");
        $this->line("  php artisan velm:db:autogen {$moduleName} --with-views");

        return self::SUCCESS;
    }
}
