<?php

declare(strict_types=1);

namespace Velm\Console\Tests\Support;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Velm\Console\Commands\DbAutogenCommand;

final class FailingDbAutogenCommand extends DbAutogenCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this->setName('db:autogen');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (! $this->laravelDatabaseAvailable()) {
            return parent::execute($input, $output);
        }

        try {
            throw new \RuntimeException('Autogen failed in test');
        } catch (\Throwable $exception) {
            $output->writeln('<error>'.$exception->getMessage().'</error>');

            return self::FAILURE;
        }
    }
}
