<?php

declare(strict_types=1);

namespace Velm\Framework\Console;

use Illuminate\Console\Command;
use Velm\Framework\VelmManager;

final class ModuleUninstallCommand extends Command
{
    protected $signature = 'velm:module:uninstall {module : Technical module name}';

    protected $description = 'Uninstall a Velm module (removes install state and UI metadata)';

    public function handle(VelmManager $velm): int
    {
        $module = (string) $this->argument('module');

        try {
            $preview = $velm->uninstallPreview($module);

            if (! $preview->canUninstall) {
                foreach ($preview->blockers() as $blocker) {
                    $this->components->error($blocker);
                }

                return self::FAILURE;
            }

            $velm->uninstall($module);
        } catch (\Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Uninstalled {$module}.");

        return self::SUCCESS;
    }
}
