<?php

declare(strict_types=1);

namespace Velm\Console\Tests\Support;

use Velm\Console\Commands\ModuleInstallCommand;

final class NoLaravelModuleInstallCommand extends ModuleInstallCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this->setName('module:install');
    }

    protected function laravelDatabaseAvailable(): bool
    {
        return false;
    }
}
