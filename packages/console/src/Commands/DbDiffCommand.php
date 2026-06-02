<?php

declare(strict_types=1);

namespace Velm\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Velm\Console\Support\ModuleRoots;
use Velm\Modules\ModuleInstaller;

#[AsCommand(name: 'db:diff', description: 'Show schema drift for an installed module')]
final class DbDiffCommand extends Command
{
    protected function configure(): void
    {
        $this->addOption('module', null, InputOption::VALUE_REQUIRED, 'Technical module name');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (! class_exists(\Illuminate\Support\Facades\DB::class)) {
            $output->writeln('<error>db:diff requires a bootstrapped Laravel application (database).</error>');

            return Command::FAILURE;
        }

        $module = $input->getOption('module');

        if (! is_string($module) || $module === '') {
            $output->writeln('<error>Pass --module=&lt;name&gt; (e.g. partners).</error>');

            return Command::FAILURE;
        }

        $installer = new ModuleInstaller;
        $roots = ModuleRoots::resolve();

        try {
            $diff = $installer->diff($module, $roots);
        } catch (\Throwable $exception) {
            $output->writeln('<error>'.$exception->getMessage().'</error>');

            return Command::FAILURE;
        }

        if ($diff->isEmpty()) {
            $output->writeln("<info>No schema drift for {$module}.</info>");

            return Command::SUCCESS;
        }

        $output->writeln("<info>Schema drift for {$module}:</info>");

        foreach ($diff->newTables as [$table, $modelClass]) {
            $output->writeln("  + table {$table} ({$modelClass})");
        }

        foreach ($diff->newColumns as [$table, $column]) {
            $output->writeln("  + column {$table}.{$column}");
        }

        foreach ($diff->orphanColumns as [$table, $column]) {
            $output->writeln("  - orphan column {$table}.{$column} (manual DROP or SYNC_HOOK)");
        }

        foreach ($diff->alterations as $alteration) {
            $output->writeln($alteration->cliLine());
        }

        return Command::SUCCESS;
    }
}
