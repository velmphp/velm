<?php

declare(strict_types=1);

namespace Velm\Framework\Console;

use Illuminate\Console\Command;
use Velm\Console\Support\ModuleRoots;
use Velm\Modules\ModuleInstaller;
use Velm\Modules\ModuleRepository;

final class ModuleSyncAllCommand extends Command
{
    protected $signature = 'velm:module:sync-all';

    protected $description = 'Reload DATA (views, menus) for every installed module';

    public function handle(ModuleInstaller $installer, ModuleRepository $repository): int
    {
        $roots = ModuleRoots::resolve();
        $names = $repository->installedNames();

        if ($names === []) {
            $this->components->warn('No installed modules.');

            return self::SUCCESS;
        }

        foreach ($names as $name) {
            try {
                $installer->sync($name, $roots);
                $this->components->info("Synced {$name}.");
            } catch (\Throwable $exception) {
                $this->components->error("{$name}: {$exception->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
