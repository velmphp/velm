<?php

declare(strict_types=1);

namespace Velm\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Velm\Console\Support\ModuleRoots;
use Velm\Console\Support\RequiresLaravelDatabase;
use Velm\Modules\ModuleInstaller;

#[AsCommand(name: 'module:uninstall', description: 'Uninstall a module')]
class ModuleUninstallCommand extends Command
{
    use RequiresLaravelDatabase;
    protected function configure(): void
    {
        $this->addArgument('module', InputArgument::REQUIRED, 'Technical module name');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (! $this->laravelDatabaseAvailable()) {
            $output->writeln('<error>module:uninstall requires a bootstrapped Laravel application (database).</error>');

            return Command::FAILURE;
        }

        $module = (string) $input->getArgument('module');
        $installer = new ModuleInstaller;

        try {
            $roots = ModuleRoots::resolve();
            $protected = ModuleRoots::bootstrapModules();
            $preview = $installer->uninstallPreview($module, $roots, $protected);

            if (! $preview->canUninstall) {
                foreach ($preview->blockers() as $blocker) {
                    $output->writeln('<error>'.$blocker.'</error>');
                }

                return Command::FAILURE;
            }

            $installer->uninstall($module, $roots, $protected);
        } catch (\Throwable $exception) {
            $output->writeln('<error>'.$exception->getMessage().'</error>');

            return Command::FAILURE;
        }

        $output->writeln("<info>Uninstalled {$module}.</info>");

        return Command::SUCCESS;
    }
}
