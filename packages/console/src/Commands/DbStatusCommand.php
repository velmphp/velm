<?php

declare(strict_types=1);

namespace Velm\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Velm\Console\Support\ModuleRoots;
use Velm\Modules\ModuleInstaller;

#[AsCommand(name: 'db:status', description: 'Show installed module versions vs manifest')]
final class DbStatusCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (! class_exists(\Illuminate\Support\Facades\DB::class)) {
            $output->writeln('<error>db:status requires a bootstrapped Laravel application (database).</error>');

            return Command::FAILURE;
        }

        $rows = (new ModuleInstaller)->schemaStatus(ModuleRoots::resolve());

        if ($rows === []) {
            $output->writeln('<comment>No installed modules.</comment>');

            return Command::SUCCESS;
        }

        $output->writeln(sprintf('%-20s %-12s %-12s %s', 'Module', 'Installed', 'Manifest', 'Status'));

        foreach ($rows as $row) {
            $style = $row['status'] === 'upgrade' ? 'comment' : 'info';
            $output->writeln(sprintf(
                '<%s>%-20s %-12s %-12s %s</%s>',
                $style,
                $row['name'],
                $row['installed'] ?? '—',
                $row['manifest'],
                $row['status'],
                $style,
            ));
        }

        return Command::SUCCESS;
    }
}
