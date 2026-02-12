<?php

namespace Velm\Core\Commands\Generator;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand('velm:make:migration', 'Create a new migration file within the current module context')]
class MigrateMakeCommand extends Command implements PromptsForMissingInput
{
    use InteractsWithVelmModules;

    //    protected $signature = 'velm:make:migration {name : The name of the migration}
    //        {--create= : The table to be created}
    //        {--table= : The table to migrate}
    //        {--path= : The location where the migration file should be created}';
    protected $name = 'velm:make:migration';

    protected $description = 'Create a new migration file within the current module context';

    public function __invoke(): void
    {
        $module = $this->resolveModule();
        $path = $this->option('path') ?? '';
        $absolutePath = rtrim($module->entryPoint::getMigrationsPath($path), DIRECTORY_SEPARATOR);
        // Call the native generator command with resolved path and realpath as true
        $this->call('make:migration', [
            'name' => $this->argument('name'),
            '--create' => $this->option('create'),
            '--table' => $this->option('table'),
            '--path' => $absolutePath,
            '--realpath' => true,
        ]);
    }

    protected function getArguments(): array
    {
        return [
            new InputArgument('name', InputArgument::REQUIRED, 'The name of the migration'),
            new InputArgument('module', InputArgument::OPTIONAL, 'The package name of the module to create the migration in (e.g modules/accounting)'),
        ];
    }

    protected function getOptions(): array
    {
        return [
            new InputOption('create', null, InputOption::VALUE_OPTIONAL, 'The table to be created'),
            new InputOption('table', null, InputOption::VALUE_OPTIONAL, 'The table to migrate'),
            new InputOption('path', null, InputOption::VALUE_OPTIONAL, 'The location where the migration file should be created, relative to the module\'s migrations directory'),
        ];
    }
}
