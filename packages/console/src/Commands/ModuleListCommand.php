<?php

declare(strict_types=1);

namespace Velm\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Velm\Console\Support\ModuleRoots;
use Velm\Modules\ModuleInstaller;

#[AsCommand(name: 'module:list', description: 'List discovered and installed modules')]
final class ModuleListCommand extends Command
{
    protected function configure(): void
    {
        $this->addOption('discovered-only', null, InputOption::VALUE_NONE, 'Skip database state');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $installer = new ModuleInstaller;
        $roots = ModuleRoots::resolve();

        if ($roots === []) {
            $output->writeln('<error>No addon paths configured.</error>');

            return Command::FAILURE;
        }

        $discoveredOnly = (bool) $input->getOption('discovered-only');

        if ($discoveredOnly) {
            $this->renderDiscovered($output, $installer, $roots);

            return Command::SUCCESS;
        }

        if (! class_exists(\Illuminate\Support\Facades\DB::class)) {
            $output->writeln('<comment>Laravel database not bootstrapped — showing discovered modules only.</comment>');
            $output->writeln('');
            $this->renderDiscovered($output, $installer, $roots);

            return Command::SUCCESS;
        }

        $table = new Table($output);
        $table->setHeaders(['Module', 'State', 'Version', 'Depends', 'Summary']);

        foreach ($installer->catalog($roots) as $row) {
            $table->addRow([
                $row['name'],
                $row['state'],
                $row['version'],
                $row['depends'],
                $row['summary'],
            ]);
        }

        $table->render();

        return Command::SUCCESS;
    }

    /**
     * @param  list<string>  $roots
     */
    private function renderDiscovered(OutputInterface $output, ModuleInstaller $installer, array $roots): void
    {
        $table = new Table($output);
        $table->setHeaders(['Module', 'Version', 'Depends', 'Summary']);

        foreach ($installer->resolveOrder($installer->discover($roots)) as $spec) {
            $table->addRow([
                $spec->name,
                $spec->versionString(),
                $spec->depends === [] ? '—' : implode(', ', $spec->depends),
                $spec->summary,
            ]);
        }

        $table->render();
    }
}
