<?php

declare(strict_types=1);

namespace Velm\Framework\Console;

use Illuminate\Console\Command;
use Velm\Framework\VelmManager;

final class ModuleUninstallCommand extends Command
{
    protected $signature = 'velm:module:uninstall
                            {module : Technical module name}
                            {--drop-schema : Drop database tables owned exclusively by this module (dev only)}';

    protected $description = 'Uninstall a Velm module (removes install state and UI metadata)';

    public function handle(VelmManager $velm): int
    {
        $module = (string) $this->argument('module');
        $dropSchema = (bool) $this->option('drop-schema');

        if ($dropSchema && ! $this->laravel->environment(['local', 'testing'])) {
            $this->components->error('The --drop-schema option is only allowed in local and testing environments.');

            return self::FAILURE;
        }

        try {
            $preview = $velm->uninstallPreview($module);

            if (! $preview->canUninstall) {
                foreach ($preview->blockers() as $blocker) {
                    $this->components->error($blocker);
                }

                return self::FAILURE;
            }

            $velm->uninstall($module, $dropSchema);
        } catch (\Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $message = "Uninstalled {$module}.";

        if ($dropSchema) {
            $message .= ' Module-owned tables were dropped.';
        }

        $this->components->info($message);

        return self::SUCCESS;
    }
}
