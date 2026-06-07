<?php

declare(strict_types=1);

namespace Velm\Console\Tests\Support;

use Velm\Console\Commands\DbAutogenCommand;

final class NoLaravelDbAutogenCommand extends DbAutogenCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this->setName('db:autogen');
    }

    protected function laravelDatabaseAvailable(): bool
    {
        return false;
    }
}
