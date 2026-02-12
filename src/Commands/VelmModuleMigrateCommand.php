<?php

namespace Velm\Core\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Velm\Core\Commands\Generator\InteractsWithVelmModules;

class VelmModuleMigrateCommand extends Command implements PromptsForMissingInput
{
    use InteractsWithVelmModules;

    protected $name = 'velm:module:migrate';

    protected $description = 'Run migrations for a specific Velm module';

    public function __invoke(): int
    {
        $module = $this->resolveModule();
        $parameters = [
            '--path' => $module->entryPoint::getMigrationsPath($this->option('path') ?? ''),
            '--realpath' => true,
            '--seeder' => $this->getSeederClass(),
            '--isolated' => $this->option('isolated'),
        ];
        if ($this->option('force')) {
            $parameters['--force'] = true;
        }
        if ($this->option('seed')) {
            $parameters['--seed'] = true;
        }
        if ($this->option('pretend')) {
            $parameters['--pretend'] = true;
        }
        if ($this->option('graceful')) {
            $parameters['--graceful'] = true;
        }
        if ($this->option('step')) {
            $parameters['--step'] = true;
        }

        $this->call('migrate', $parameters);

        return \Symfony\Component\Console\Command\Command::SUCCESS;
    }

    protected function getArguments(): array
    {
        return [
            new InputArgument(
                'module',
                InputArgument::OPTIONAL,
                'The package name of the module to migrate (e.g., vendor/package)'
            ),
        ];
    }

    protected function getSeederClass()
    {
        if ($this->option('seed')) {
            $module = $this->resolveModule();

            return $module->entryPoint::getSeederClass('DatabaseSeeder');
        }

        return null;
    }

    protected function getOptions()
    {
        return [
            new InputOption('path', null, InputOption::VALUE_OPTIONAL, 'The location where the migration files are located, relative to the module\'s migrations directory'), new InputOption('force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production.'), new InputOption('seed', null, InputOption::VALUE_NONE, 'Indicates if the seed task should be re-run.'), new InputOption('pretend', null, InputOption::VALUE_NONE, 'Dump the SQL queries that would be run.'), new InputOption('graceful', null, InputOption::VALUE_NONE, 'Continue with other migrations even if some fail.'), new InputOption('step', null, InputOption::VALUE_NONE, 'Run migrations so they can be rolled back individually.'), new InputOption('isolated', null, InputOption::VALUE_NONE, 'Run only the migrations for this module without considering dependencies.'),
        ];
    }
}
