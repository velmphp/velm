<?php

declare(strict_types=1);

namespace Velm\Framework\Console;

use Illuminate\Console\Command;
use Velm\Console\Support\ModuleRoots;
use Velm\Modules\Migrations\ModuleMigrationAutogen;
use Velm\Modules\ModuleInstaller;

final class DbAutogenCommand extends Command
{
    protected $signature = 'velm:db:autogen
                            {--module= : Technical module name}
                            {--version= : Explicit target version (e.g. 0.2.0)}
                            {--dry-run : Print the migration file without writing}';

    protected $description = 'Write a versioned migration file and bump manifest VERSION';

    public function handle(ModuleInstaller $installer): int
    {
        $module = $this->option('module');

        if (! is_string($module) || $module === '') {
            $this->components->error('Pass --module=<name> (e.g. partners).');

            return self::FAILURE;
        }

        $roots = ModuleRoots::resolve();
        $specs = $installer->discover($roots);

        if (! isset($specs[$module])) {
            $this->components->error("Module {$module} was not discovered.");

            return self::FAILURE;
        }

        $spec = $specs[$module];
        $dryRun = (bool) $this->option('dry-run');
        $explicitVersion = $this->option('version');
        $targetVersion = is_string($explicitVersion) && $explicitVersion !== '' ? $explicitVersion : null;

        try {
            $diff = $installer->diff($module, $roots);
            $from = $spec->version;
            $autogen = new ModuleMigrationAutogen;
            $to = $autogen->targetVersion($from, $targetVersion);
            $result = $autogen->write($spec, $diff, $from, $to, $dryRun);
        } catch (\Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($dryRun) {
            $this->line($result);

            return self::SUCCESS;
        }

        $this->components->info("Wrote {$result}");
        $this->components->info('Bumped VERSION: '.implode('.', array_map('strval', $from)).' → '.implode('.', array_map('strval', $to)));

        if ($diff->isEmpty()) {
            $this->components->warn('Migration body is a no-op — review whether you need it.');
        }

        return self::SUCCESS;
    }
}
