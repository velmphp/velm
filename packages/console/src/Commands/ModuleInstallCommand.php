<?php

declare(strict_types=1);

namespace Velm\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Velm\Console\Support\ModuleRoots;
use Velm\Modules\ModuleInstaller;

#[AsCommand(name: 'module:install', description: 'Install a module and its dependencies')]
final class ModuleInstallCommand extends Command
{
    protected function configure(): void
    {
        $this->addArgument('module', InputArgument::REQUIRED, 'Technical module name');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (! class_exists(\Illuminate\Support\Facades\DB::class)) {
            $output->writeln('<error>module:install requires a bootstrapped Laravel application (database).</error>');

            return Command::FAILURE;
        }

        $module = (string) $input->getArgument('module');
        $installer = new ModuleInstaller;

        try {
            $installer->install($module, ModuleRoots::resolve());
        } catch (\Throwable $exception) {
            $output->writeln('<error>'.$exception->getMessage().'</error>');

            return Command::FAILURE;
        }

        $output->writeln("<info>Installed {$module} (and dependencies).</info>");

        return Command::SUCCESS;
    }
}
