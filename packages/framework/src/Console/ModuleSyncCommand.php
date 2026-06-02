<?php

declare(strict_types=1);

namespace Velm\Framework\Console;

use Illuminate\Console\Command;
use Velm\Console\Support\ModuleRoots;
use Velm\Modules\ModuleInstaller;

final class ModuleSyncCommand extends Command
{
    protected $signature = 'velm:module:sync {module : Technical module name}';

    protected $description = 'Reload DATA files for an installed module';

    public function handle(ModuleInstaller $installer): int
    {
        $module = (string) $this->argument('module');

        try {
            $installer->sync($module, ModuleRoots::resolve());
        } catch (\Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Synced {$module}.");

        return self::SUCCESS;
    }
}
