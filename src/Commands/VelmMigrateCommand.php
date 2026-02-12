<?php

namespace Velm\Core\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Velm\Core\Commands\Generator\InteractsWithVelmModules;
use Velm\Core\Persistence\ModuleState;
use Velm\Core\Support\Helpers\ConsoleLogType;

#[AsCommand('velm:migrate', 'Run database migrations for a single module or all modules')]
class VelmMigrateCommand extends Command implements PromptsForMissingInput
{
    use InteractsWithVelmModules;

    protected $name = 'velm:migrate';

    protected function canRunOnAllModules(): bool
    {
        return true;
    }

    public function __invoke(): int
    {
        $this->resolveModule();

        if (! $this->allModules) {
            return $this->call('velm:module:migrate', [
                'module' => $this->argument('module'),
            ]);
        }
        // Loop through all active modules and call module:migrate on each of them
        $modules = velm()->registry()->modules()->installed();
        if (empty($modules)) {
            velm_utils()->consoleLog('No installed modules found. Nothing to migrate.', ConsoleLogType::WARNING);

            return \Symfony\Component\Console\Command\Command::SUCCESS;
        }
        foreach ($modules as $module) {
            velm_utils()->consoleLog("Migrating module: {$module->package}", ConsoleLogType::INTRO);
            /**
             * @var ModuleState $module
             */
            $this->call('velm:module:migrate', [
                'module' => $module->package,
            ]);
            velm_utils()->consoleLog("Finished migrating module: {$module->package}", ConsoleLogType::SUCCESS);
        }

        return \Symfony\Component\Console\Command\Command::SUCCESS;
    }

    protected function getArguments(): array
    {
        return [
            new InputArgument('module', InputArgument::OPTIONAL, 'Module name'),
        ];
    }

    protected function getOptions()
    {
        return [
            // all options from migrate command
            new InputOption('database', null, InputOption::VALUE_OPTIONAL, 'The database connection to use'),
            new InputOption('force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production.'),
            new InputOption('path', null, InputOption::VALUE_OPTIONAL, 'The path(s) to the migrations files to be executed.'),
            new InputOption('realpath', null, InputOption::VALUE_NONE, 'Indicates any provided migration file paths are pre-resolved absolute paths.'),
            new InputOption('schema-path', null, InputOption::VALUE_OPTIONAL, 'The path to a schema dump file to load before migrations.'),
            new InputOption('pretend', null, InputOption::VALUE_NONE, 'Dump the SQL queries that would be run.'),
            new InputOption('seed', null, InputOption::VALUE_NONE, 'Indicates if the seed task should be re-run.'),
            new InputOption('step', null, InputOption::VALUE_NONE, 'Run migrations so they can be rolled back individually.'),
            new InputOption('graceful', null, InputOption::VALUE_NONE, 'Continue running other module migrations even if one fails.'),
            new InputOption('isolated', null, InputOption::VALUE_NONE, 'Run each module migration in a separate process to isolate them from each other.'),
        ];
    }
}
