<?php

declare(strict_types=1);

namespace Velm\Console\Tests\Support;

use Velm\Console\Commands\ModuleSyncCommand;

final class NoLaravelModuleSyncCommand extends ModuleSyncCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this->setName('module:sync');
    }

    protected function laravelDatabaseAvailable(): bool
    {
        return false;
    }
}
