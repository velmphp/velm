<?php

declare(strict_types=1);

namespace Velm\Console\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as Orchestra;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

abstract class ConsoleTestCase extends Orchestra
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        $app['config']->set('velm.addon_paths', [
            dirname(__DIR__, 2).'/modules/modules',
        ]);

        $app['config']->set('velm.bootstrap_modules', ['base']);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(dirname(__DIR__, 2).'/modules/database/migrations');
    }

    protected function runCommand(Command $command, array $input = []): CommandTester
    {
        $app = new Application;
        $app->add($command);
        $tester = new CommandTester($app->find($command->getName()));
        $tester->execute($input);

        return $tester;
    }
}
