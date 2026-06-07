<?php

declare(strict_types=1);

namespace Velm\Console\Tests\Support;

use Velm\Console\Commands\MigrateCommand;

final class NoLaravelMigrateCommand extends MigrateCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this->setName('migrate');
    }

    protected function laravelDatabaseAvailable(): bool
    {
        return false;
    }
}
