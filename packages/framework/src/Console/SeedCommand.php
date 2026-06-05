<?php

declare(strict_types=1);

namespace Velm\Framework\Console;

use Illuminate\Console\Command;
use Velm\Framework\VelmManager;

final class SeedCommand extends Command
{
    protected $signature = 'velm:seed {--module= : Seed only one module (and its dependencies)}';

    protected $description = 'Run manifest seeders for installed modules';

    public function handle(VelmManager $velm): int
    {
        $module = $this->option('module');
        $module = is_string($module) && $module !== '' ? $module : null;

        try {
            $velm->seed($module);
        } catch (\Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info('Velm seed completed.');

        return self::SUCCESS;
    }
}

