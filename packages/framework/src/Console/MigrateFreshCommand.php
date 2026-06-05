<?php

declare(strict_types=1);

namespace Velm\Framework\Console;

use Illuminate\Console\Command;
use Velm\Framework\VelmManager;

final class MigrateFreshCommand extends Command
{
    protected $signature = 'velm:migrate:fresh
        {--yes : Skip confirmation prompt}
        {--module=* : Also migrate these modules after bootstrap}';

    protected $description = 'Drop all Velm-owned tables and reinstall bootstrap modules';

    public function handle(VelmManager $velm): int
    {
        $modules = array_values(array_filter(array_map('strval', (array) $this->option('module'))));

        if (! $this->option('yes')) {
            $confirmed = $this->confirm(
                'This will DROP Velm tables and reinstall modules. Continue?',
                false,
            );

            if (! $confirmed) {
                $this->components->info('Aborted.');

                return self::SUCCESS;
            }
        }

        try {
            $velm->migrateFresh(modules: $modules);
        } catch (\Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info('Velm migrate:fresh completed.');

        return self::SUCCESS;
    }
}

