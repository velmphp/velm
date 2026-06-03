<?php

declare(strict_types=1);

namespace Velm\Framework\Tests;

use Livewire\LivewireServiceProvider;
use Velm\Framework\VelmServiceProvider;
use Velm\Modules\Tests\TestCase as ModulesTestCase;

abstract class TestCase extends ModulesTestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('filesystems.default', 'local');
        $app['config']->set('filesystems.disks.local', [
            'driver' => 'local',
            'root' => storage_path('framework/testing/disks/local'),
            'throw' => false,
        ]);
    }

    protected function getPackageProviders($app): array
    {
        return [
            ...parent::getPackageProviders($app),
            LivewireServiceProvider::class,
            VelmServiceProvider::class,
        ];
    }
}
