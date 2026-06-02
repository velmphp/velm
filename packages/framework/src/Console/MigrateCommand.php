<?php

declare(strict_types=1);

namespace Velm\Framework\Console;

use Illuminate\Console\Command;
use Velm\Console\Support\ModuleRoots;
use Velm\Modules\ModuleInstaller;

final class MigrateCommand extends Command
{
    protected $signature = 'velm:migrate {--module= : Install one module and its dependencies}';

    protected $description = 'Install or upgrade bootstrap Velm modules (or a single module with --module)';

    public function handle(ModuleInstaller $installer): int
    {
        try {
            $module = $this->option('module');

            if (is_string($module) && $module !== '') {
                $installer->migrate($module, ModuleRoots::resolve());
                $this->components->info("Migrated {$module} (and dependencies).");
            } else {
                $bootstrap = ModuleRoots::bootstrapModules();
                $installer->installBootstrap(ModuleRoots::resolve(), $bootstrap);
                $this->components->info('Migrated bootstrap modules: '.implode(', ', $bootstrap).'.');
            }
        } catch (\Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
