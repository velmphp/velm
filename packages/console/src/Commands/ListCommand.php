<?php

declare(strict_types=1);

namespace Velm\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'list', description: 'List available Velm commands')]
final class ListCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Velm CLI scaffold</info> — see PLAN.md for the full command catalog.');
        $output->writeln('');
        $output->writeln('Module runtime (Phase 0):');
        $output->writeln('  module:list, module:install, module:sync, migrate');
        $output->writeln('');
        $output->writeln('Planned:');
        $output->writeln('  db:diff, db:status, db:autogen, make:module, make:model, make:view');
        return Command::SUCCESS;
    }
}
