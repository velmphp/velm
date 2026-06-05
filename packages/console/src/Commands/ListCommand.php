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
        $output->writeln('<info>Velm commands run via Laravel Artisan</info> (from your app root):');
        $output->writeln('  php artisan list velm');
        $output->writeln('  php artisan velm:migrate');
        $output->writeln('  php artisan velm:module:list');
        $output->writeln('  php artisan velm:module:uninstall partners_ext');
        $output->writeln('  php artisan velm:db:diff --module=partners');
        return Command::SUCCESS;
    }
}
