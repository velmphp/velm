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

#[AsCommand(name: 'migrate', description: 'Install bootstrap Velm modules on a fresh database')]
final class MigrateCommand extends Command
{
    protected function configure(): void
    {
        $this->addOption('module', null, InputOption::VALUE_REQUIRED, 'Install one module (and dependencies)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (! class_exists(\Illuminate\Support\Facades\DB::class)) {
            $output->writeln('<error>migrate requires a bootstrapped Laravel application (database).</error>');

            return Command::FAILURE;
        }

        $installer = new ModuleInstaller;
        $roots = ModuleRoots::resolve();

        try {
            $module = $input->getOption('module');

            if (is_string($module) && $module !== '') {
                $installer->install($module, $roots);
                $output->writeln("<info>Installed {$module} (and dependencies).</info>");
            } else {
                $bootstrap = ModuleRoots::bootstrapModules();
                $installer->installBootstrap($roots, $bootstrap);
                $output->writeln('<info>Installed bootstrap modules: '.implode(', ', $bootstrap).'.</info>');
            }
        } catch (\Throwable $exception) {
            $output->writeln('<error>'.$exception->getMessage().'</error>');

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
