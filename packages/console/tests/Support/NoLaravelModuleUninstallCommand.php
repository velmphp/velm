<?php

declare(strict_types=1);

namespace Velm\Console\Tests\Support;

use Velm\Console\Commands\ModuleUninstallCommand;

final class NoLaravelModuleUninstallCommand extends ModuleUninstallCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this->setName('module:uninstall');
    }

    protected function laravelDatabaseAvailable(): bool
    {
        return false;
    }
}
