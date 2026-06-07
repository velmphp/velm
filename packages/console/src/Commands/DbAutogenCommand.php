<?php

declare(strict_types=1);

namespace Velm\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Velm\Console\Support\ModuleRoots;
use Velm\Modules\Migrations\ModuleMigrationAutogen;
use Velm\Modules\ModuleInstaller;

#[AsCommand(name: 'db:autogen', description: 'Write a versioned migration file and bump manifest VERSION')]
final class DbAutogenCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption('module', null, InputOption::VALUE_REQUIRED, 'Technical module name')
            ->addOption('target-version', null, InputOption::VALUE_REQUIRED, 'Explicit target version (e.g. 0.2.0)')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Print the migration file without writing');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (! class_exists(\Illuminate\Support\Facades\DB::class)) {
            $output->writeln('<error>db:autogen requires a bootstrapped Laravel application (database).</error>');

            return Command::FAILURE;
        }

        $module = $input->getOption('module');

        if (! is_string($module) || $module === '') {
            $output->writeln('<error>Pass --module=&lt;name&gt; (e.g. partners).</error>');

            return Command::FAILURE;
        }

        $installer = new ModuleInstaller;
        $roots = ModuleRoots::resolve();
        $specs = $installer->discover($roots);

        if (! isset($specs[$module])) {
            $output->writeln("<error>Module {$module} was not discovered.</error>");

            return Command::FAILURE;
        }

        $spec = $specs[$module];
        $dryRun = (bool) $input->getOption('dry-run');
        $explicitVersion = $input->getOption('target-version');
        $targetVersion = is_string($explicitVersion) ? $explicitVersion : null;

        try {
            $diff = $installer->diff($module, $roots);
            $from = $spec->version;
            $to = (new ModuleMigrationAutogen)->targetVersion($from, $targetVersion);
            $autogen = new ModuleMigrationAutogen;
            $result = $autogen->write($spec, $diff, $from, $to, $dryRun);
        } catch (\Throwable $exception) {
            $output->writeln('<error>'.$exception->getMessage().'</error>');

            return Command::FAILURE;
        }

        if ($dryRun) {
            $output->writeln($result);

            return Command::SUCCESS;
        }

        $output->writeln("<info>Wrote {$result}</info>");
        $output->writeln('<info>Bumped VERSION: '.implode('.', array_map('strval', $from)).' → '.implode('.', array_map('strval', $to)).'</info>');

        if ($diff->isEmpty()) {
            $output->writeln('<comment>Migration body is a no-op — review whether you need it.</comment>');
        }

        return Command::SUCCESS;
    }
}
