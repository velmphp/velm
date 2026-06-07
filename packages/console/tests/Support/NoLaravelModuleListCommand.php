<?php

declare(strict_types=1);

namespace Velm\Console\Tests\Support;

use Velm\Console\Commands\ModuleListCommand;

final class NoLaravelModuleListCommand extends ModuleListCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this->setName('module:list');
    }

    protected function laravelDatabaseAvailable(): bool
    {
        return false;
    }
}
