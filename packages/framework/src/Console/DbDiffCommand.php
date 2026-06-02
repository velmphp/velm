<?php

declare(strict_types=1);

namespace Velm\Framework\Console;

use Illuminate\Console\Command;
use Velm\Console\Support\ModuleRoots;
use Velm\Modules\ModuleInstaller;

final class DbDiffCommand extends Command
{
    protected $signature = 'velm:db:diff {--module= : Technical module name}';

    protected $description = 'Show schema drift for an installed module';

    public function handle(ModuleInstaller $installer): int
    {
        $module = $this->option('module');

        if (! is_string($module) || $module === '') {
            $this->components->error('Pass --module=<name> (e.g. partners).');

            return self::FAILURE;
        }

        try {
            $diff = $installer->diff($module, ModuleRoots::resolve());
        } catch (\Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($diff->isEmpty()) {
            $this->components->info("No schema drift for {$module}.");

            return self::SUCCESS;
        }

        $this->components->info("Schema drift for {$module}:");

        foreach ($diff->newTables as [$table, $modelClass]) {
            $this->line("  + table {$table} ({$modelClass})");
        }

        foreach ($diff->newColumns as [$table, $column]) {
            $this->line("  + column {$table}.{$column}");
        }

        foreach ($diff->orphanColumns as [$table, $column]) {
            $this->line("  - orphan column {$table}.{$column} (manual DROP or SYNC_HOOK)");
        }

        foreach ($diff->alterations as $alteration) {
            $this->line($alteration->cliLine());

            if ($alteration->kind !== 'set_not_null') {
                continue;
            }

            $nulls = $installer->countNullRows($alteration->table, $alteration->column);

            if ($nulls > 0) {
                $this->line("      → migrate will not apply SET NOT NULL yet: {$nulls} NULL row(s) in {$alteration->table}.{$alteration->column}");
                $this->line('      → backfill in SYNC_HOOK or a migration script, then velm:migrate again');
            } else {
                $this->line("      → no NULL rows; velm:migrate should apply SET NOT NULL on {$alteration->table}.{$alteration->column}");
            }
        }

        return self::SUCCESS;
    }
}
