<?php

declare(strict_types=1);

namespace Velm\Framework\Console;

use Illuminate\Console\Command;
use Velm\Console\Scaffold\AutogenViewEnsurer;
use Velm\Console\Scaffold\ScaffoldRegistryLoader;
use Velm\Console\Support\ModuleRoots;
use Velm\Modules\Migrations\ModuleMigrationAutogen;
use Velm\Modules\ModuleInstaller;

final class DbAutogenCommand extends Command
{
    protected $signature = 'velm:db:autogen
                            {--module= : Technical module name}
                            {--target-version= : Explicit target version (e.g. 0.2.0)}
                            {--with-views : Scaffold list+form views for models touched by the schema diff}
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
        $explicitVersion = $this->option('target-version');
        $targetVersion = is_string($explicitVersion) && $explicitVersion !== '' ? $explicitVersion : null;

        try {
            $diff = $installer->diff($module, $roots);

            if ((bool) $this->option('with-views') && ! $diff->isEmpty()) {
                $registry = (new ScaffoldRegistryLoader)->loadForModule($module);
                $ensurer = new AutogenViewEnsurer;
                $affected = $ensurer->modelsAffectedByDiff($spec, $registry, $diff);
                $created = $ensurer->ensureViews($spec, $affected, $registry);

                foreach ($created as $path) {
                    $this->components->info("Created view {$path}");
                }

                if ($affected !== [] && $created === []) {
                    $this->components->warn('All affected models already have list views.');
                }
            }

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
