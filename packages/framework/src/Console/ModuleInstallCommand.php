<?php

declare(strict_types=1);

namespace Velm\Framework\Console;

use Illuminate\Console\Command;
use Velm\Console\Support\ModuleRoots;
use Velm\Modules\ModuleInstaller;

final class ModuleInstallCommand extends Command
{
    protected $signature = 'velm:module:install {module : Technical module name}';

    protected $description = 'Install or upgrade a Velm module and its dependencies';

    public function handle(ModuleInstaller $installer): int
    {
        $module = (string) $this->argument('module');

        try {
            $installer->migrate($module, ModuleRoots::resolve());
        } catch (\Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Migrated {$module} (and dependencies).");

        return self::SUCCESS;
    }
}
