<?php

declare(strict_types=1);

namespace Velm\Framework\Console;

use Illuminate\Console\Command;
use Velm\Console\Support\ModuleRoots;
use Velm\Cron\CronJob;
use Velm\Modules\ModuleInstaller;

final class CronRunCommand extends Command
{
    protected $signature = 'velm:cron:run';

    protected $description = 'Run due ir.cron jobs once';

    public function handle(ModuleInstaller $installer): int
    {
        try {
            $env = $installer->environment(ModuleRoots::resolve());
            $executed = CronJob::runDue($env);
        } catch (\Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($executed === []) {
            $this->components->info('No due cron jobs.');

            return self::SUCCESS;
        }

        $this->components->info('Executed: '.implode(', ', $executed));

        return self::SUCCESS;
    }
}
