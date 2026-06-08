<?php

declare(strict_types=1);

namespace Velm\Framework\Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Orchestra\Testbench\TestCase as Orchestra;
use Velm\Framework\Testing\InstallsVelmModules;

abstract class RefreshableTestCase extends Orchestra
{
    use InstallsVelmModules;
    use RefreshDatabase {
        InstallsVelmModules::migrateDatabases insteadof RefreshDatabase;
    }

    protected function refreshTestDatabase(): void
    {
        if (! RefreshDatabaseState::$migrated) {
            if (! static::velmTestingDatabaseIsReady()) {
                $this->migrateDatabases();

                $this->app->make(Kernel::class)->setArtisan(null);

                $this->updateLocalCacheOfInMemoryDatabases();
            } else {
                $this->bootstrapVelmForTests();
            }

            RefreshDatabaseState::$migrated = true;
        }

        $this->beginDatabaseTransaction();
    }
}
